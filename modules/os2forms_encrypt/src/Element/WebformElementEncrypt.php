<?php

namespace Drupal\os2forms_encrypt\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms_encrypt\Form\SettingsForm;

/**
 * Class WebformElementEncrypt.
 *
 * This class contains a static method to process element attributes for
 * webforms.
 *
 * @package Drupal\Core\Extension\Module\YourModule\WebformElementEncrypt
 */
class WebformElementEncrypt {

  /**
   * Processes element attributes.
   *
   * Enabled encryption as default.
   */
  public static function processWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form): array {
    $config = \Drupal::config(SettingsForm::$configName);
    if ($config->get('enabled')) {
      $element['element_encrypt']['encrypt']['#default_value'] = TRUE;
    }

    return $element;
  }

}
