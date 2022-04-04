<?php

namespace Drupal\os2forms\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for the os2forms person lookup element.
 *
 * @FormElement("os2forms_person_lookup")
 */
class Os2formsPersonLookup extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['cpr_number'] = [
      '#type' => 'textfield',
      '#title' => t('CPR number'),
      '#required' => TRUE,
    ];
    $elements['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#required' => TRUE,
    ];
    return $elements;
  }

}
