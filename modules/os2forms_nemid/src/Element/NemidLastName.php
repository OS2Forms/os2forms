<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_last_name'.
 *
 * @FormElement("os2forms_nemid_last_name")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidLastName
 */
class NemidLastName extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processNemidLastName'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNemidLastName'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidLastName'],
      ],
      '#theme' => 'input__os2forms_nemid_last_name',
    ];
  }

  /**
   * Processes a 'os2forms_nemid_last_name' element.
   */
  public static function processNemidLastName(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for #type 'os2forms_nemid_last_name'.
   */
  public static function validateNemidLastName(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidLastName(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-nemid-last-name']);
    return $element;
  }

}
