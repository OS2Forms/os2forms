<?php

namespace Drupal\os2forms_nemid\Element;

/**
 * Provides a 'os2forms_nemid_postal_code'.
 *
 * @FormElement("os2forms_nemid_postal_code")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidPostalCode
 */
class NemidPostalCode extends NemidElementBase {

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
        [$class, 'preRenderNemidPostalCode'],
      ],
      '#theme' => 'input__os2forms_nemid_postal_code',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidPostalCode(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-nemid-postal-code']);
    return $element;
  }

}
