<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a abstract NemLogin Element.
 *
 * Implements the prepopulate logic.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class NemloginElementBase extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
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

    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
    $authProviderPlugin = ($authPluginId) ? $authProviderService->getPluginInstance($authPluginId) : $authProviderService->getActivePlugin();

    $prepopulateKey = $this->getPrepopulateFieldFieldKey($element);

    if ($authProviderPlugin->isAuthenticated()) {
      $value = $authProviderPlugin->fetchValue($prepopulateKey);
      $element['#value'] = $value;
    }
  }

}
