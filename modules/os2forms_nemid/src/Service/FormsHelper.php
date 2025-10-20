<?php

namespace Drupal\os2forms_nemid\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\os2forms_nemid\Element\NemidCompanyCvrFetchData;
use Drupal\os2forms_nemid\Element\NemidCompanyPNumber;
use Drupal\os2forms_nemid\Element\NemidCprFetchData;
use Drupal\os2web_audit\Service\Logger;
use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_nemlogin\Plugin\AuthProviderInterface;
use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Drupal\webform\WebformSubmissionInterface;

/**
 * FormsHelper.
 *
 * Helper functions for os2forms_nemid.
 *
 * @package Drupal\os2forms_nemid\Service
 */
class FormsHelper {
  const ADDRESS_PROTECTION_STATE = 'os2forms_nemlogin_address_protection';

  /**
   * Defines NemID login address protection display error option.
   */
  const WEBFORM_NEM_LOGIN_ADDRESS_PROTECTION_DISPLAY_ERROR = 'os2forms_nemlogin_address_protection_display_error';

  /**
   * Defines NemID login address protection display default behaviour.
   */
  const WEBFORM_NEM_LOGIN_ADDRESS_PROTECTION_DEFAULT_BEHAVIOUR = 'os2forms_nemlogin_address_protection_default_behaviour';

  /**
   * Defines NemID login address related elements.
   */
  private const WEBFORM_NEM_LOGIN_ADDRESS_PROTECTION_ELEMENT_TYPES = [
    'os2forms_nemid_address',
    'os2forms_nemid_street',
    'os2forms_nemid_house_nr',
    'os2forms_nemid_floor',
    'os2forms_nemid_apartment_nr',
    'os2forms_nemid_postal_code',
    'os2forms_nemid_city',
    'os2forms_nemid_kommunekode',
    'os2forms_nemid_coaddress',
  ];

  /**
   * Auth provider service.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  private $authProviderService;

  /**
   * DataLookupPlugin manager.
   *
   * @var \Drupal\os2web_datalookup\Plugin\DataLookupManager
   */
  private $dataLookManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * An audit logger.
   *
   * @var \Drupal\os2web_audit\Service\Logger
   */
  private Logger $auditLogger;

  /**
   * Constructor.
   *
   * @param \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService
   *   Auth provider service.
   * @param \Drupal\os2web_datalookup\Plugin\DataLookupManager $dataLookPluginManager
   *   Datalookup plugin manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match service.
   * @param \Drupal\os2web_audit\Service\Logger $auditLogger
   *   Audit logger.
   */
  public function __construct(
    AuthProviderService $authProviderService,
    DataLookupManager $dataLookPluginManager,
    RouteMatchInterface $routeMatch,
    Logger $auditLogger,
  ) {
    $this->authProviderService = $authProviderService;
    $this->dataLookManager = $dataLookPluginManager;
    $this->routeMatch = $routeMatch;
    $this->auditLogger = $auditLogger;
  }

  /**
   * Retrieves the CPRLookupResult which is stored in form_state.
   *
   * If there is no CPRLookupResult, it is requested and saved for future uses.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CprLookupResult|null
   *   CPRLookupResult or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function retrieveCprLookupResult(FormStateInterface $form_state) {
    // Handling CPR being changed/reset.
    if ($form_state->isRebuilding() && $this->isDataFetchTriggeredBy(NemidCprFetchData::getFormElementId(), $form_state)) {
      $storage = $form_state->getStorage();
      unset($storage['cprLookupResult']);
      $form_state->setStorage($storage);
    }

    /** @var Drupal\os2web_datalookup\LookupResult\CprLookupResult $cprLookupResult */
    $cprLookupResult = NULL;

    // Trying to fetch person data from cache.
    if ($form_state->has('cprLookupResult')) {
      $cprLookupResult = $form_state->get('cprLookupResult');
    }
    else {
      // Cached version does not exist.
      //
      // Making the request to the plugin, and storing the data, so that it's
      // available on the next element within the same webform render.
      if ($cprLookupResult = $this->lookupPersonData($form_state)) {
        if ($cprLookupResult->isSuccessful()) {
          $form_state->set('cprLookupResult', $cprLookupResult);
        }
      }
    }

    // We need the auth provider for logging purposes.
    $authProviderPlugin = $this->getAuthProvider($form_state);

    $userCpr = $authProviderPlugin->fetchValue('cpr');
    $lookedUpCpr = $cprLookupResult->getFieldValue('cpr');

    // Current method is called whenever the webform is rendered.
    // As a result, it is also called in situations where both
    // userCpr and lookedUpCpr does not make sense, e.g.
    // adding an element to a webform.
    if ($userCpr && $lookedUpCpr) {
      $this->auditLogger->info('DataLookup', 'User with cpr ' . $userCpr . ' looked up cpr ' . $lookedUpCpr);
    }

    return $cprLookupResult;
  }

  /**
   * Retrieves the cached CPRLookupResult which is stored in form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CprLookupResult|null
   *   CPRLookupResult or NULL.
   */
  public static function retrieveCachedCprLookupResult(FormStateInterface $form_state) {
    return $form_state->has('cprLookupResult') ? $form_state->get('cprLookupResult') : NULL;
  }

  /**
   * Performs lookup of person data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CprLookupResult
   *   CPRLookupResult as object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function lookupPersonData(FormStateInterface $form_state) {
    $cprResult = new CprLookupResult();
    $cpr = NULL;

    /** @var \Drupal\webform\WebformSubmissionInterface Interface $webformSubmission */
    $webformSubmission = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webformSubmission->getWebform();
    $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // Getting auth plugin ID override.
    $authPluginId = NULL;
    if (isset($webformNemidSettings['session_type']) && !empty($webformNemidSettings['session_type'])) {
      $authPluginId = $webformNemidSettings['session_type'];
    }

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
    $authProviderPlugin = ($authPluginId) ? $this->authProviderService->getPluginInstance($authPluginId) : $this->authProviderService->getActivePlugin();

    // 1. Getting CPR from Nemlogin.
    if ($authProviderPlugin->isAuthenticated()) {
      $cpr = $authProviderPlugin->fetchValue('cpr');
    }
    // 2. Getting CPR from CPR fetch data field.
    elseif ($form_state->isRebuilding() && $this->isDataFetchTriggeredBy(NemidCprFetchData::getFormElementId(), $form_state)) {
      $cpr = $this->getDataFetchTriggerValue(NemidCprFetchData::getValueElementName(), $form_state);
    }

    if ($cpr) {
      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCprInterface $cprPlugin */
      $cprPlugin = $this->dataLookManager->createDefaultInstanceByGroup('cpr_lookup');

      if ($cprPlugin->isReady()) {
        $cprResult = $cprPlugin->lookup($cpr);
      }
    }

    return $cprResult;
  }

  /**
   * Retrieves the CompanyLookupResult which is stored in form_state.
   *
   * If there is no CVRLookupResult, it is requested and saved for future uses.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CompanyLookupResult|null
   *   CompanyLookupResult or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function retrieveCompanyLookupResult(FormStateInterface $form_state) {
    // Resetting cached companyLookupResult.
    if ($form_state->isRebuilding() && ($this->isDataFetchTriggeredBy(NemidCompanyPNumber::getFormElementId(), $form_state) || $this->isDataFetchTriggeredBy(NemidCompanyCvrFetchData::getFormElementId(), $form_state))) {
      $storage = $form_state->getStorage();
      unset($storage['companyLookupResult']);
      $form_state->setStorage($storage);
    }

    /** @var \Drupal\os2web_datalookup\LookupResult\CompanyLookupResult $companyLookupResult */
    $companyLookupResult = NULL;

    // Trying to fetch company data from cache.
    if ($form_state->has('companyLookupResult')) {
      $companyLookupResult = $form_state->get('companyLookupResult');
    }
    else {
      // Cached version does not exist.
      //
      // Making the request to the plugin, and storing the data, so that it's
      // available on the next element within the same webform render.
      if ($companyLookupResult = $this->lookupCompanyData($form_state)) {
        if ($companyLookupResult->isSuccessful()) {
          $form_state->set('companyLookupResult', $companyLookupResult);
        }
      }
    }

    return $companyLookupResult;
  }

  /**
   * Performs lookup of the company data.
   *
   * Uses CVR or P-number based services depending on the available values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CompanyLookupResult
   *   CompanyLookupResult as object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function lookupCompanyData(FormStateInterface $form_state) {
    $companyResult = new CompanyLookupResult();
    $cvr = NULL;
    $pNumber = NULL;

    /** @var \Drupal\webform\WebformSubmissionInterface Interface $webformSubmission */
    $webformSubmission = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webformSubmission->getWebform();
    $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // Getting auth plugin ID override.
    $authPluginId = NULL;
    if (isset($webformNemidSettings['session_type']) && !empty($webformNemidSettings['session_type'])) {
      $authPluginId = $webformNemidSettings['session_type'];
    }

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
    $authProviderPlugin = ($authPluginId) ? $this->authProviderService->getPluginInstance($authPluginId) : $this->authProviderService->getActivePlugin();

    // 1. Attempt to fetch CVR from login.
    if ($authProviderPlugin->isAuthenticated()) {
      $cvr = $authProviderPlugin->fetchValue('cvr');
    }
    // 2. Handling P-number fetch data.
    elseif ($form_state->isRebuilding() && $this->isDataFetchTriggeredBy(NemidCompanyPNumber::getFormElementId(), $form_state)) {
      $pNumber = $this->getDataFetchTriggerValue(NemidCompanyPNumber::getValueElementName(), $form_state);
    }
    // 3. Handling CVR fetch data.
    elseif ($form_state->isRebuilding() && $this->isDataFetchTriggeredBy(NemidCompanyCvrFetchData::getFormElementId(), $form_state)) {
      $cvr = $this->getDataFetchTriggerValue(NemidCompanyCvrFetchData::getValueElementName(), $form_state);
    }

    // Performing the lookup.
    if ($cvr) {
      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCompanyInterface $cvrPlugin */
      $cvrPlugin = $this->dataLookManager->createDefaultInstanceByGroup('cvr_lookup');

      if ($cvrPlugin->isReady()) {
        $companyResult = $cvrPlugin->lookup($cvr);
      }
    }
    elseif ($pNumber) {
      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCompanyInterface $pNumberPlugin */
      $pNumberPlugin = $this->dataLookManager->createDefaultInstanceByGroup('pnumber_lookup');

      if ($pNumberPlugin->isReady()) {
        $companyResult = $pNumberPlugin->lookup($pNumber);
      }
    }

    return $companyResult;
  }

  /**
   * Checks if form rebuild triggered by data fetch element.
   *
   * @param string $dataFetchElementType
   *   Data fetch element type to check against.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  protected function isDataFetchTriggeredBy($dataFetchElementType, FormStateInterface $form_state) {
    if ($triggerElement = $form_state->getTriggeringElement()) {
      // Checking trigger element parent.
      $form_array = $form_state->getCompleteForm();
      $triggerElParents = $triggerElement['#array_parents'];

      // Removing last element = current trigger elements.
      array_pop($triggerElParents);
      $parentElement = NestedArray::getValue($form_array, $triggerElParents);

      // Checking if parent element is the desired type.
      if ($parentElement && isset($parentElement['#type']) && $parentElement['#type'] == $dataFetchElementType) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if form rebuild triggered by data fetch element.
   *
   * @param string $dataFetchValueFieldName
   *   Data fetch value fields name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return string
   *   Field value.
   */
  protected function getDataFetchTriggerValue($dataFetchValueFieldName, FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();

    $elementParents = $triggerElement['#parents'];

    // Removing last element = current trigger elements.
    array_pop($elementParents);

    array_push($elementParents, $dataFetchValueFieldName);
    $value = $form_state->getValue($elementParents);

    return $value;
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  public function webformSubmissionPrepareForm(WebformSubmissionInterface $webformSubmission, string $operation, FormStateInterface $formState): void {
    // Only perform address protection check when displaying submission form.
    $accessCheckRouteNames = [
      // Webform attached to a node.
      'entity.node.canonical',
      // Creating a new submission.
      'entity.webform.canonical',
      // Editing a submission.
      'entity.webform_submission.edit_form',
    ];

    if (!in_array($this->routeMatch->getRouteName(), $accessCheckRouteNames, TRUE)) {
      return;
    }

    // Check if hide address protection is selected.
    $hideForm = $webformSubmission->getWebform()->getThirdPartySettings('os2forms')['os2forms_nemid_address_protection']['nemlogin_hide_form'] ?? NULL;

    if ($hideForm === self::WEBFORM_NEM_LOGIN_ADDRESS_PROTECTION_DISPLAY_ERROR) {
      $cprResult = $this->retrieveCprLookupResult($formState);

      if ($cprResult && $cprResult->isNameAddressProtected()) {

        // Check if any element violating address
        // protection is present in webform.
        $elements = $webformSubmission->getWebform()->getElementsDecodedAndFlattened();

        foreach ($elements as $element) {

          if (in_array($element['#type'], self::WEBFORM_NEM_LOGIN_ADDRESS_PROTECTION_ELEMENT_TYPES)) {

            // Violation detected,
            // mark form state with temporary key and return.
            $message = $webformSubmission->getWebform()->getThirdPartySettings('os2forms')['os2forms_nemid_address_protection']['nemlogin_hide_message'];

            $formState->setTemporaryValue(self::ADDRESS_PROTECTION_STATE, [
              'access' => FALSE,
              'message' => $message,
            ]);

            return;
          }
        }
      }
    }
  }

  /**
   * Get active auth provider plugin.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface
   *   The active auth provider plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getAuthProvider(FormStateInterface $form_state): AuthProviderInterface {
    /** @var \Drupal\webform\WebformSubmissionInterface Interface $webformSubmission */
    $webformSubmission = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webformSubmission->getWebform();
    $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // Getting auth plugin ID override.
    $authPluginId = NULL;
    if (!empty($webformNemidSettings['session_type'])) {
      $authPluginId = $webformNemidSettings['session_type'];
    }

    return ($authPluginId) ? $this->authProviderService->getPluginInstance($authPluginId) : $this->authProviderService->getActivePlugin();
  }

}
