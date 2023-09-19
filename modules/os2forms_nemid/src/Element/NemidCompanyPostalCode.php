<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_company_postal_code'.
 *
 * @FormElement("os2forms_nemid_company_postal_code")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyPostalCode
 */
class NemidCompanyPostalCode extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processNemidCompanyPostalCode'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNemidCompanyPostalCode'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidCompanyPostalCode'],
      ],
      '#theme' => 'input__os2forms_nemid_company_postal_code',
    ];
  }

  /**
   * Processes a 'os2forms_nemid_company_postal_code' element.
   */
  public static function processNemidCompanyPostalCode(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation for 'os2forms_nemid_company_postal_code'.
   */
  public static function validateNemidCompanyPostalCode(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidCompanyPostalCode(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, [
      'form-text',
      'os2forms-nemid-company-postal-code',
    ]);
    return $element;
  }

}
