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
    $cpCvrData = NULL;

    if ($form_state->has('servicePlatformenCvrData')) {
      $cpCvrData = $form_state->get('servicePlatformenCvrData');
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

        // TODO: make the request to plugin, and remove dummy data.
        // $cpCvrData = calling the service with $cvr;.
        $cpCvrData = [
          'status' => TRUE,
          'cvr' => 'cvr',
          'company_name' => 'company_name',
          'company_street' => 'company_street',
          'company_house_nr' => 'company_house_nr',
          'company_floor' => 'company_floor',
          'company_zipcode' => 'company_zipcode',
          'company_city' => 'company_city',
        ];
        // Making composite field, company_address.
        $cpCvrData['company_address'] = $cpCvrData['company_street'] . ' ' . $cpCvrData['company_house_nr'] . ' ' . $cpCvrData['company_floor'];

        $form_state->set('servicePlatformenCvrData', $cpCvrData);
      }
    }

    if (!empty($cpCvrData)) {
      if (isset($cpCvrData[$prepopulateKey])) {
        $value = $cpCvrData[$prepopulateKey];
        $element['#value'] = $value;
      }
    }
  }

}
