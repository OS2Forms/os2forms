<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Hidden;

/**
 * Provides a 'os2forms_mitid_child_other_guardian'.
 *
 * @FormElement("os2forms_mitid_child_ther_guardian")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidCpr
 */
class MitidChildOtherGuardian extends Hidden {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processMitidChildOtherGuardian'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateMitidChildOtherGuardian'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMitidChildOtherGuardian'],
      ],
      '#theme' => 'input__os2forms_mitid_child_other_guardian',
    ];
  }

  /**
   * Processes a 'os2forms_mitid_child_other_guardian' element.
   */
  public static function processMitidChildOtherGuardian(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for #type 'os2forms_mitid_child_other_guardian'.
   */
  public static function validateMitidChildOtherGuardian(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMitidChildOtherGuardian(array $element) {
    $element = parent::preRenderHidden($element);
    static::setAttributes($element, ['os2forms-mitid-child-other-guardian']);
    return $element;
  }

}
