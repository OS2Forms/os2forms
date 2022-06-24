<?php

namespace Drupal\os2forms_nemid\Element;

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
        '#value' => $element['#fetch_button_title'] ?? t('Hent'),
        '#limit_validation_errors' => [
          [
            $element['#webform_key'],
          ],
        ],
      ];
    }

    return $elements;
  }

}
