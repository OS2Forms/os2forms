<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a abstract ServicePlatformenCvr Element.
 *
 * Implements the prepopulate logic.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class ServiceplatformenCvrElementBase extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
    $prepopulateKey = $this->getPrepopulateFieldFieldKey();

    // Fetch value from serviceplatforment CVR.
    $spCvrData = NULL;

    if ($form_state->has('servicePlatformenCvrData')) {
      $spCvrData = $form_state->get('servicePlatformenCvrData');
    }
    else {
      // Making the request to the plugin, and storing the information on the
      // form, so that it's available on the next element within the same
      // webform render.

      /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
      $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');
      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
      $plugin = $authProviderService->getActivePlugin();

      if ($plugin->isAuthenticated()) {
        $cvr = $plugin->fetchValue('cvr');

        $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');
        /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCVR $servicePlatformentCvrPlugin */
        $servicePlatformentCvrPlugin = $pluginManager->createInstance('serviceplatformen_cvr');

        if ($servicePlatformentCvrPlugin->isReady()) {
          $spCvrData = $servicePlatformentCvrPlugin->getInfo($cvr);
          // Making composite field, company_address.
          $spCvrData['company_address'] = $spCvrData['company_street'] . ' ' . $spCvrData['company_house_nr'] . ' ' . $spCvrData['company_floor'];

          // Making composite field, city.
          $spCvrData['company_city'] = $spCvrData['company_zipcode'] . ' ' . $spCvrData['company_city'];

          $form_state->set('servicePlatformenCvrData', $spCvrData);
        }
      }
    }

    if (!empty($spCvrData)) {
      if (isset($spCvrData[$prepopulateKey])) {
        $value = $spCvrData[$prepopulateKey];
        $element['#value'] = $value;
      }
    }
  }

}
