<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'os2forms_nemid_company_p_number'.
 *
 * @FormElement("os2forms_nemid_company_p_number")
 */
class NemidCompanyPNumber extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    if ($element) {
      $elements['p_number_value'] = [
        '#type' => 'textfield',
        '#title' => $element['#title'],
      ];

      $elements['p_number_submit'] = [
        '#type' => 'button',
        '#value' => isset($element['#fetch_button_title']) ? $element['#fetch_button_title'] : t('Hent'),
      ];
    }

    return $elements;
  }

}
