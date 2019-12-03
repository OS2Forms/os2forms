<?php

namespace Drupal\os2forms_dawa\Element;

/**
 * Provides a DAWA Matrikula Autocomplete element.
 *
 * @FormElement("os2forms_dawa_block")
 */
class DawaBlock extends DawaElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    $info = parent::getInfo();
    $info['#pre_render'][] = [$class, 'preRenderDawaBlock'];
    return $info;
  }

  /**
   * Prepares a render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderDawaBlock(array $element) {
    static::setAttributes($element, ['os2forms-dawa-block']);
    return $element;
  }

}
