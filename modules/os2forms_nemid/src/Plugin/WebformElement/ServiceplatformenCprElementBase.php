<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

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

    // Fetch value from serviceplatforment CPR.
    $spCrpData = NULL;

    if ($form_state->has('servicePlatformenCprData')) {
      $spCrpData = $form_state->get('servicePlatformenCprData');
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
        $cpr = $plugin->fetchValue('cpr');

        $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');
        /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCPR $servicePlatformentCprPlugin */
        $servicePlatformentCprPlugin = $pluginManager->createInstance('serviceplatformen_cpr');

        if ($servicePlatformentCprPlugin->isReady()) {
          $spCrpData = $servicePlatformentCprPlugin->getAddress($cpr);
          // Making composite field, address.
          $spCrpData['address'] = $spCrpData['road'] . ' ' . $spCrpData['road_no'] . ' ' . $spCrpData['floor'] . ' ' . $spCrpData['door'];

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

}
