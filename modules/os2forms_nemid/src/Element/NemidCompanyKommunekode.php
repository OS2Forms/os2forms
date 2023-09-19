<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_company_kommunekode'.
 *
 * @FormElement("os2forms_nemid_company_kommunekode")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyKommunekode
 */
class NemidCompanyKommunekode extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processNemidCompanyKommunekode'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNemidCompanyKommunekode'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidCompanyKommunekode'],
      ],
      '#theme' => 'input__os2forms_nemid_company_kommunekode',
    ];
  }

  /**
   * Processes a 'os2forms_nemid_company_kommunekode' element.
   */
  public static function processNemidCompanyKommunekode(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation for 'os2forms_nemid_company_kommunekode'.
   */
  public static function validateNemidCompanyKommunekode(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidCompanyKommunekode(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, [
      'form-text',
      'os2forms-nemid-company-kommunekode',
    ]);
    return $element;
  }

}
