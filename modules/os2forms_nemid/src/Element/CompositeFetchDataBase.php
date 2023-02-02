<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Interface FetchDataInterface.
 *
 * Describing FetchData elements functions.
 */
abstract class CompositeFetchDataBase extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    if ($element) {
      $elements[static::getValueElementName()] = [
        '#type' => 'textfield',
        '#title' => $element['#title'],
      ];

      $elements[static::getSubmitElementName()] = [
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

  /**
   * Returns form element ID.
   *
   * @return string
   *   Id of the form element.
   */
  abstract public static function getFormElementId();

  /**
   * Returns the name of the element where for value field.
   *
   * @return string
   *   Field name.
   */
  abstract public static function getValueElementName();

  /**
   * Returns the name of the element for submit button.
   *
   * @return string
   *   Field name.
   */
  abstract public static function getSubmitElementName();

}
