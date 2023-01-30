<?php

namespace Drupal\os2forms_nemid\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\os2web_datalookup\LookupResult\CvrLookupResult;
use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_nemlogin\Service\AuthProviderService;

/**
 * FormsHelper.
 *
 * Helper functions for os2forms_nemid.
 *
 * @package Drupal\os2forms_nemid\Service
 */
class FormsHelper {

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
   * Constructor.
   *
   * @param \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService
   *   Auth provider service.
   * @param \Drupal\os2web_datalookup\Plugin\DataLookupManager $dataLookPluginManager
   *   Datalookup plugin manager.
   */
  public function __construct(AuthProviderService $authProviderService, DataLookupManager $dataLookPluginManager) {
    $this->authProviderService = $authProviderService;
    $this->dataLookManager = $dataLookPluginManager;
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
    if ($form_state->isRebuilding() && $this->isCprNumberTrigger($form_state)) {
      // Resetting the current field value - it fetch is successfull,
      // it will be filled later.
      $element['#value'] = NULL;

      $cpr = $this->getCprNumberValue($form_state);

      // If another cprFetchData, resetting cached cprLookupResult.
      if (strcmp($cpr, $form_state->get('nemidCprFetchData')) !== 0) {
        $storage = $form_state->getStorage();
        unset($storage['cprLookupResult']);
        $form_state->setStorage($storage);

        // Saving the new CPR-number.
        $form_state->set('nemidCprFetchData', $cpr);
      }
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
      if ($cprLookupResult = $this->fetchPersonData($form_state)) {
        if ($cprLookupResult->isSuccessful()) {
          $form_state->set('cprLookupResult', $cprLookupResult);
        }
      }
    }

    return $cprLookupResult;
  }

  /**
   * Makes request to serviceplatformen.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CprLookupResult
   *   CPRLookupResult as object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function fetchPersonData(FormStateInterface $form_state) {
    $cprResult = new CprLookupResult();

    // 1. Getting CPR from Nemlogin.
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $nemloginAuth = $this->authProviderService->getActivePlugin();

    if ($nemloginAuth->isAuthenticated()) {
      $cpr = $nemloginAuth->fetchValue('cpr');
    }
    // 2. Getting CPR from CPR fetch data field
    else {
      if ($form_state->isRebuilding() && $this->isCprNumberTrigger($form_state)) {
        $cpr = $this->getCprNumberValue($form_state);
      }
    }

    if ($cpr) {
      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCPRInterface $cprPlugin */
      $cprPlugin = $this->dataLookManager->createDefaultInstanceByGroup('cpr_lookup');

      if ($cprPlugin->isReady()) {
        $cprResult = $cprPlugin->lookup($cpr);
      }

    }

    return $cprResult;
  }

  /**
   * Retrieves the CVRLookupResult which is stored in form_state.
   *
   * If there is no CBVRLookupResult, it is requested and saved for future uses.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CvrLookupResult|null
   *   CvrLookupResult or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function retrieveCvrLookupResult(FormStateInterface $form_state) {
    // Handling P-number being changed/reset.
    if ($form_state->isRebuilding() && $this->isPnumberTrigger($form_state)) {
      // Resetting the current field value - it fetch is successfull,
      // it will be filled later.
      $element['#value'] = NULL;

      $pNumber = $this->getPnumberValue($form_state);

      // If another pNumber, resetting cached servicePlatformenCompanyData.
      if (strcmp($pNumber, $form_state->get('nemidCompanyPNumber')) !== 0) {
        $storage = $form_state->getStorage();
        unset($storage['servicePlatformenCompanyData']);
        $form_state->setStorage($storage);

        // Saving the new P-number.
        $form_state->set('nemidCompanyPNumber', $pNumber);
      }
    }

    /** @var \Drupal\os2web_datalookup\LookupResult\CvrLookupResult $cvrLookupResult */
    $cvrLookupResult = NULL;

    // Trying to fetch company data from cache.
    if ($form_state->has('cvrLookupResult')) {
      $cvrLookupResult = $form_state->get('cvrLookupResult');
    }
    else {
      // Cached version does not exist.
      //
      // Making the request to the plugin, and storing the data, so that it's
      // available on the next element within the same webform render.
      if ($cvrLookupResult = $this->fetchCompanyData($form_state)) {
        if ($cvrLookupResult->isSuccessful()) {
          $form_state->set('cvrLookupResult', $cvrLookupResult);
        }
      }
    }

    return $cvrLookupResult;
  }

  /**
   * Makes request to serviceplatformen.
   *
   * Uses CVR or P-number based services depending on the available values/
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\os2web_datalookup\LookupResult\CvrLookupResult
   *   CVRLookupResult as object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function fetchCompanyData(FormStateInterface $form_state) {
    $cvrResult = new CvrLookupResult();

    // 1. Attempt to fetch data via CVR.
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $nemloginAuth = $this->authProviderService->getActivePlugin();

    if ($nemloginAuth->isAuthenticated()) {
      $cvr = $nemloginAuth->fetchValue('cvr');
      $spCompanyData['cvr'] = $cvr;

      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCvr $cvrPlugin */
      $cvrPlugin = $this->dataLookManager->createDefaultInstanceByGroup('cvr_lookup');

      if ($cvrPlugin->isReady()) {
        $cvrResult = $cvrPlugin->lookup($cvr);
      }
    }
    // 2. Attempt to fetch data via P-number.
    else {
      if ($form_state->isRebuilding() && $this->isPnumberTrigger($form_state)) {
        $pNumber = $this->getPnumberValue($form_state);

        if ($pNumber) {
          /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenPNumber $servicePlatformentPNumberPlugin */
          $servicePlatformentPNumberPlugin = $this->dataLookManager->createInstance('serviceplatformen_p_number');

          if ($servicePlatformentPNumberPlugin->isReady()) {
            $cvrResult = $servicePlatformentPNumberPlugin->lookup($pNumber);
          }
        }
      }
    }

    return $cvrResult;
  }

  /**
   * Checks if form rebuild trigger is CPR-number fetch button.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  protected function isCprNumberTrigger(FormStateInterface $form_state) {
    if ($triggerElement = $form_state->getTriggeringElement()) {
      // Checking trigger element parent.
      $form_array = $form_state->getCompleteForm();
      $triggerElParents = $triggerElement['#array_parents'];

      // Removing last element = current trigger elements.
      array_pop($triggerElParents);
      $parentElement = NestedArray::getValue($form_array, $triggerElParents);

      // Checking if parent element is 'os2forms_nemid_cpr_fetch_data'.
      if ($parentElement && isset($parentElement['#type']) && $parentElement['#type'] == 'os2forms_nemid_cpr_fetch_data') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets the value from CPR-number field.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return string
   *   P-Number value from the field.
   */
  protected function getCprNumberValue(FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();

    $pNumberParents = $triggerElement['#parents'];

    // Removing last element = current trigger elements.
    array_pop($pNumberParents);

    array_push($pNumberParents, 'cpr_fetch_data_value');
    $pNumber = $form_state->getValue($pNumberParents);

    return $pNumber;
  }

  /**
   * Checks if form rebuild trigger is P-number fetch button.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isPnumberTrigger(FormStateInterface $form_state) {
    if ($triggerElement = $form_state->getTriggeringElement()) {
      // Checking trigger element parent.
      $form_array = $form_state->getCompleteForm();
      $triggerElParents = $triggerElement['#array_parents'];

      // Removing last element = current trigger elements.
      array_pop($triggerElParents);
      $parentElement = NestedArray::getValue($form_array, $triggerElParents);

      // Checking if parent element is 'os2forms_nemid_company_p_number'.
      if ($parentElement && isset($parentElement['#type']) && $parentElement['#type'] == 'os2forms_nemid_company_p_number') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets the value from P-number field.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return string
   *   P-Number value from the field.
   */
  public function getPnumberValue(FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();

    $pNumberParents = $triggerElement['#parents'];

    // Removing last element = current trigger elements.
    array_pop($pNumberParents);

    array_push($pNumberParents, 'p_number_value');
    $pNumber = $form_state->getValue($pNumberParents);

    return $pNumber;
  }

}
