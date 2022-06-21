<?php

namespace Drupal\os2forms_autocomplete\Element;

use Drupal\webform\Element\WebformAutocomplete;

/**
 * Provides a DAWA Address Autocomplete element.
 *
 * @FormElement("os2forms_autocomplete")
 */
class AutocompleteElement extends WebformAutocomplete {

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformAutocomplete($element) {
    static::setAttributes($element, ['os2forms-autocomplete']);
    return $element;
  }

}
