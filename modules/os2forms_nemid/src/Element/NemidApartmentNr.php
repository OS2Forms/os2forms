<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_apartment_nr'.
 *
 * @FormElement("os2forms_nemid_apartment_nr")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidHouseNr
 */
class NemidApartmentNr extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo() + [
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNemidApartmentNr'],
      ],
      '#theme' => 'input__os2forms_nemid_apartment_nr',
    ];
  }

    /**
   * {@inheritdoc}
   */
  public static function preRenderNemidApartmentNr(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-nemid-apartment-nr']);
    return $element;
  }

}
