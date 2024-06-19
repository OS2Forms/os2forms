<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Hidden;

/**
 * Provides a 'os2forms_mitid_child_other_guardian'.
 *
 * @FormElement("os2forms_mitid_child_other_guardian")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 */
class MitidChildOtherGuardian extends Hidden {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    $parentInfo = parent::getInfo() + [
      '#element_validate' => [
        [$class, 'validateMitidChildOtherGuardian'],
      ],
    ];

    // Adding custom #pre_render.
    $parentInfo['#pre_render'] = [
      [$class, 'preRenderMitidChildOtherGuardian'],
    ];

    return $parentInfo;
  }

  /**
   * Webform element validation handler 'os2forms_mitid_child_other_guardian'.
   */
  public static function validateMitidChildOtherGuardian(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMitidChildOtherGuardian($element) {
    $element = parent::preRenderHidden($element);
    static::setAttributes($element, [
      'os2forms-mitid-child-other-guardian',
      'js-form-type-os2forms-mitid-child-other-guardian',
    ]);

    return $element;
  }

}
