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
    $prepopulateKey = $this->getPrepopulateFieldFieldKey();

    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $plugin = $authProviderService->getActivePlugin();

    if ($plugin->isAuthenticated()) {
      $value = $plugin->fetchValue($prepopulateKey);
      $element['#value'] = $value;
    }
  }

}
