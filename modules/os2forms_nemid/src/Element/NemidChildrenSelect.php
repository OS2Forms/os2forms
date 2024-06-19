<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Render\Element\Select;

/**
 * Provides a 'os2forms_nemid_children_select'.
 *
 * @FormElement("os2forms_nemid_children_select")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\os2forms_nemid\Element\NemidChildrenSelect
 */
class NemidChildrenSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#ajax' => [
        'callback' => [MitidChildrenSelectAjaxBehaviour::class, 'mitidChildrenSelectAjax'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Please wait...'),
        ],
      ],
    ];
  }

}
