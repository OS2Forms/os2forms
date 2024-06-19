<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_mitid_child_address'.
 *
 * @FormElement("os2forms_mitid_child_address")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidName
 */
class MitidChildAddress extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processMitidChildAddress'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateMitidChildAddress'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMitidChildAddress'],
      ],
      '#theme' => 'input__os2forms_mitid_child_address',
    ];
  }

  /**
   * Processes a 'os2forms_mitid_child_address' element.
   */
  public static function processMitidChildAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for 'os2forms_mitid_child_address'.
   */
  public static function validateMitidChildAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMitidChildAddress(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-mitid-child-address']);
    return $element;
  }

}
