<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_mitid_child_postal_code'.
 *
 * @FormElement("os2forms_mitid_child_postal_code")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidPostalCode
 */
class MitidChildPostalCode extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processMitidChildPostalCode'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateMitidChildPostalCode'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMitidChildPostalCode'],
      ],
      '#theme' => 'input__os2forms_mitid_child_postal_code',
    ];
  }

  /**
   * Processes a 'os2forms_mitid_child_postal_code' element.
   */
  public static function processMitidChildPostalCode(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for 'os2forms_mitid_child_postal_code'.
   */
  public static function validateMitidChildPostalCode(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMitidChildPostalCode(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-mitid-child-postal-code']);
    return $element;
  }

}
