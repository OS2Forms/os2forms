<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_mitid_child_coaddress'.
 *
 * @FormElement("os2forms_mitid_child_coaddress")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidCoaddress
 */
class MitidChildCoaddress extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processMitidChildCoaddress'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateMitidChildCoaddress'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMitidChildCoaddress'],
      ],
      '#theme' => 'input__os2forms_mitid_child_coaddress',
    ];
  }

  /**
   * Processes a 'os2forms_mitid_child_coaddress' element.
   */
  public static function processMitidChildCoaddress(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for #type 'os2forms_mitid_child_coaddress'.
   */
  public static function validateMitidChildCoaddress(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMitidChildCoaddress(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-mitid-child-coaddress']);
    return $element;
  }

}
