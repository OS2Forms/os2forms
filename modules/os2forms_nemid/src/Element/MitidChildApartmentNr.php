<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_mitid_child_apartment_nr'.
 *
 * @FormElement("os2forms_mitid_child_apartment_nr")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidApartmentNr
 */
class MitidChildApartmentNr extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processMitidChildApartmentNr'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateMitidChildApartmentNr'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMitidChildApartmentNr'],
      ],
      '#theme' => 'input__os2forms_mitid_child_apartmentNr',
    ];
  }

  /**
   * Processes a 'os2forms_mitid_child_apartmentNr' element.
   */
  public static function processMitidChildApartmentNr(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler 'os2forms_mitid_child_apartmentNr'.
   */
  public static function validateMitidChildApartmentNr(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMitidChildApartmentNr(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-mitid-child-apartment-nr']);
    return $element;
  }

}
