<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_company_street'.
 *
 * @FormElement("os2forms_nemid_company_street")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyApartmentNr
 */
class NemidCompanyApartmentNr extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processNemidCompanyApartmentNr'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNemidCompanyApartmentNr'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidCompanyApartmentNr'],
      ],
      '#theme' => 'input__os2forms_nemid_company_apartment_nr',
    ];
  }

  /**
   * Processes a 'os2forms_nemid_company_apartment_nr' element.
   */
  public static function processNemidCompanyApartmentNr(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation for 'os2forms_nemid_company_apartment_nr'.
   */
  public static function validateNemidCompanyApartmentNr(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidCompanyApartmentNr(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, [
      'form-text',
      'os2forms-nemid-company-apartment-nr',
    ]);
    return $element;
  }

}
