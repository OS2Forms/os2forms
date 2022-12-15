<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'os2forms_nemid_uuid'.
 *
 * @FormElement("os2forms_nemid_uuid")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidUuid
 */
class NemidUuid extends NemidElementBase {

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
        [$class, 'preRenderNemidUuid'],
      ],
      '#theme' => 'input__os2forms_nemid_uuid',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNemidUuid(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, ['form-text', 'os2forms-nemid-uuid']);
    return $element;
  }

}
