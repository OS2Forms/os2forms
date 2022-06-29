<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a abstract ServicePlatformenCpr Element.
 *
 * Implements the prepopulate logic.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class ServiceplatformenCprElementBase extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
    $prepopulateKey = $this->getPrepopulateFieldFieldKey();
    $spCrpData = NULL;

    // Handling CPR being changed/reset.
    if ($form_state->isRebuilding() && $this->isCprNumberTrigger($form_state)) {
      // Resetting the current field value - it fetch is successfull,
      // it will be filled later.
      $element['#value'] = NULL;

      $cpr = $this->getCprNumberValue($form_state);

      // If another cprFetchData, resetting cached servicePlatformenCprData.
      if (strcmp($cpr, $form_state->get('nemidCprFetchData')) !== 0) {
        $storage = $form_state->getStorage();
        unset($storage['servicePlatformenCprData']);
        $form_state->setStorage($storage);

        // Saving the new CPR-number.
        $form_state->set('nemidCprFetchData', $cpr);
      }
    }

    // Trying to fetch person data from cache.
    if ($form_state->has('servicePlatformenCprData')) {
      $spCrpData = $form_state->get('servicePlatformenCprData');
    }
    else {
      // Cached version does not exist.
      //
      // Making the request to the plugin, and storing the data, so that it's
      // available on the next element within the same webform render.
      if ($spCrpData = $this->fetchPersonData($form_state)) {
        if (isset($spCrpData['status']) && $spCrpData['status']) {
          $form_state->set('servicePlatformenCprData', $spCrpData);
        }
      }
    }

    if (!empty($spCrpData)) {
      if (isset($spCrpData[$prepopulateKey])) {
        $value = $spCrpData[$prepopulateKey];
        $element['#value'] = $value;
      }
    }
  }

  /**
   * Makes request to serviceplatformen.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array|null
   *   Company information or NULL if request could not be performed.
   */
  private function fetchPersonData(FormStateInterface $form_state) {
    $spCrpData = NULL;

    // 1. Getting CPR from Nemlogin.
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $nemloginAuth = $authProviderService->getActivePlugin();
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
      $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');
      /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCPR $servicePlatformentCprPlugin */
      $servicePlatformentCprPlugin = $pluginManager->createInstance('serviceplatformen_cpr');

      if ($servicePlatformentCprPlugin->isReady()) {
        $spCrpData = $servicePlatformentCprPlugin->getAddress($cpr);
      }

      // Post fetch procedure - manipulating the address fields.
      if (isset($spCrpData['status']) && $spCrpData['status']) {
        // Making composite field, address.
        $spCrpData['address'] = $spCrpData['road'] . ' ' . $spCrpData['road_no'] . ' ' . $spCrpData['floor'] . ' ' . $spCrpData['door'];

        // Making composite field, city.
        $spCrpData['city'] = $spCrpData['zipcode'] . ' ' . $spCrpData['city'];
      }
    }

    return $spCrpData;
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
  public function isCprNumberTrigger(FormStateInterface $form_state) {
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
  public function getCprNumberValue(FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();

    $pNumberParents = $triggerElement['#parents'];

    // Removing last element = current trigger elements.
    array_pop($pNumberParents);

    array_push($pNumberParents, 'cpr_fetch_data_value');
    $pNumber = $form_state->getValue($pNumberParents);

    return $pNumber;
  }

}
