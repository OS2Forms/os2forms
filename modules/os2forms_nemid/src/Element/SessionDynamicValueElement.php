<?php

namespace Drupal\os2forms_nemid\Element;

/**
 * Provides a 'os2forms_session_dynamic_value'.
 *
 * @FormElement("os2forms_session_dynamic_value")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidUuid
 */
class SessionDynamicValueElement extends NemidElementBase {

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
        [$class, 'preRenderSessionDynamicValue'],
      ],
      '#theme' => 'input__os2forms_session_dynamic_value',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderSessionDynamicValue(array $element) {
    $element = parent::prerenderNemidElementBase($element);
    static::setAttributes($element, [
      'form-text',
      'os2forms-session-dynamic-value',
    ]);
    return $element;
  }

}
