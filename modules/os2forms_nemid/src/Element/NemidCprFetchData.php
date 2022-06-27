<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'os2forms_nemid_cpr_fetch_data'.
 *
 * @FormElement("os2forms_nemid_cpr_fetch_data")
 */
class NemidCprFetchData extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    if ($element) {
      $elements['cpr_fetch_data_value'] = [
        '#type' => 'textfield',
        '#title' => $element['#title'],
      ];

      $elements['cpr_fetch_data_submit'] = [
        '#type' => 'button',
        '#value' => $element['#fetch_button_title'] ?? t('Hent'),
        '#limit_validation_errors' => [
          [
            $element['#webform_key'],
          ],
        ],
        '#name' => $element['#webform_key'] . '-fetch',
      ];
    }

    return $elements;
  }

}
