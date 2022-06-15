<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a abstract ServicePlatformenCompany Element.
 *
 * Implements the prepopulate logic.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class ServiceplatformenCompanyElementBase extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
    $prepopulateKey = $this->getPrepopulateFieldFieldKey();
    $spCompanyData = NULL;

    // Handling P-number being changed/reset.
    if ($form_state->isRebuilding() && $this->isPNumberTrigger($form_state)) {
      // Resetting the current field value - it fetch is successfull,
      // it will be filled later.
      $element['#value'] = NULL;

      $pNumber = $this->getPNumberValue($form_state);

      // If another pNumber, resetting cached servicePlatformenCompanyData.
      if (strcmp($pNumber, $form_state->get('nemidCompanyPNumber')) !== 0) {
        $storage = $form_state->getStorage();
        unset($storage['servicePlatformenCompanyData']);
        $form_state->setStorage($storage);

        // Saving the new P-number.
        $form_state->set('nemidCompanyPNumber', $pNumber);
      }
    }

    // Trying to fetch company data from cache.
    if ($form_state->has('servicePlatformenCompanyData')) {
      $spCompanyData = $form_state->get('servicePlatformenCompanyData');
    }
    else {
      // Cached version does not exist.
      //
      // Making the request to the plugin, and storing the data, so that it's
      // available on the next element within the same webform render.
      if ($spCompanyData = $this->fetchCompanyData($form_state)) {
        $form_state->set('servicePlatformenCompanyData', $spCompanyData);
      }
    }

    if (!empty($spCompanyData)) {
      if (isset($spCompanyData[$prepopulateKey])) {
        $value = $spCompanyData[$prepopulateKey];
        $element['#value'] = $value;
      }
    }
  }

  /**
   * Makes request to serviceplatformen.
   *
   * Uses CVR or P-number based services depending on the available values/
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array|null
   *   Company information or NULL if request could not be performed.
   */
  private function fetchCompanyData(FormStateInterface $form_state) {
    $spCompanyData =  NULL;

    // 1. Attempt to fetch data via CVR.
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $nemloginAuth */
    $nemloginAuth = $authProviderService->getActivePlugin();
    if ($nemloginAuth->isAuthenticated()) {
      $cvr = $nemloginAuth->fetchValue('cvr');
      $spCompanyData['cvr'] = $cvr;
      $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');
      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCVR $servicePlatformentCvrPlugin */
      $servicePlatformentCvrPlugin = $pluginManager->createInstance('serviceplatformen_cvr');

      if ($servicePlatformentCvrPlugin->isReady()) {
        $spCompanyData = $servicePlatformentCvrPlugin->getInfo($cvr);
      }
    }
    // 2. Attempt to fetch data via P-number.
    else {
      if ($form_state->isRebuilding() && $this->isPNumberTrigger($form_state)) {
        $pNumber = $this->getPNumberValue($form_state);

        if ($pNumber) {
          $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');
          /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenPNumber $servicePlatformentPNumberPlugin */
          $servicePlatformentPNumberPlugin = $pluginManager->createInstance('serviceplatformen_p_number');

          if ($servicePlatformentPNumberPlugin->isReady()) {
            $spCompanyData = $servicePlatformentPNumberPlugin->getInfo($pNumber);
          }
        }
      }
    }

    // Post fetch procedure - manipulationg the address fields.
    if (isset($spCompanyData['status']) && $spCompanyData['status']) {
      // Making composite field, company_address.
      $spCompanyData['company_address'] = $spCompanyData['company_street'] . ' ' . $spCompanyData['company_house_nr'] . ' ' . $spCompanyData['company_floor'];

      // Making composite field, city.
      $spCompanyData['company_city'] = $spCompanyData['company_zipcode'] . ' ' . $spCompanyData['company_city'];
    }

    $this->logger->debug( __METHOD__ . ':' . __LINE__ . ' Debug data: ' . print_r($spCompanyData, 1));
    return $spCompanyData;
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
  public function isPNumberTrigger(FormStateInterface $form_state) {
    if ($triggerElement = $form_state->getTriggeringElement()) {
      //Checking trigger element parent.
      $form_array = $form_state->getCompleteForm();
      $triggerElParents = $triggerElement['#array_parents'];

      // Removing last element = current trigger elements.
      array_pop($triggerElParents);
      $parentElement = NestedArray::getValue($form_array, $triggerElParents);

      // Checking if parent element is 'os2forms_nemid_company_p_number'
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
  public function getPNumberValue(FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();

    $pNumberParents = $triggerElement['#parents'];

    // Removing last element = current trigger elements.
    array_pop($pNumberParents);

    array_push($pNumberParents, 'p_number_value');
    $pNumber = $form_state->getValue($pNumberParents);

    return $pNumber;
  }

}
