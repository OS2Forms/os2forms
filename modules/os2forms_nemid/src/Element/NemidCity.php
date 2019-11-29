<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_city'.
 *
 * @FormElement("os2forms_nemid_city")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidCity
 */
class NemidCity extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processNemidCity'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNemidCity'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidCity'],
      ],
      '#theme' => 'input__os2forms_nemid_city',
    ];
  }

  /**
   * Processes a 'os2forms_nemid_city' element.
   */
  public static function processNemidCity(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for #type 'os2forms_nemid_city'.
   */
  public static function validateNemidCity(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidCity(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-nemid-city']);
    return $element;
  }

}
