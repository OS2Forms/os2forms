<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_company_cvr'.
 *
 * @FormElement("os2forms_nemid_company_cvr")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidName
 */
class NemidCompanyCvr extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processNemidCompanyCvr'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNemidCompanyCvr'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidCompanyCvr'],
      ],
      '#theme' => 'input__os2forms_nemid_company_cvr',
    ];
  }

  /**
   * Processes a 'os2forms_nemid_company_cvr' element.
   */
  public static function processNemidCompanyCvr(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add and manipulate your element's properties and callbacks.
    return $element;
  }

  /**
   * Webform element validation handler for #type 'os2forms_nemid_company_cvr'.
   */
  public static function validateNemidCompanyCvr(&$element, FormStateInterface $form_state, &$complete_form) {
    // Here you can add custom validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidCompanyCvr(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, [
      'form-text',
      'os2forms-nemid-company-cvr',
    ]);
    return $element;
  }

}
