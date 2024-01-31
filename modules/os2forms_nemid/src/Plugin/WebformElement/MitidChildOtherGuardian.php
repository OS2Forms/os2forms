<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Hidden;

/**
 * Provides a 'os2forms_mitid_child_other_guardian' element.
 *
 * @WebformElement(
 *   id = "os2forms_mitid_child_other_guardian",
 *   label = @Translation("MitID Child Other Guardian"),
 *   description = @Translation("Provides a MitID Child Other Guardian element."),
 *   category = @Translation("NemID"),
 * )
 */
class MitidChildOtherGuardian extends Hidden {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
    $element_properties = $form_state->get('element_properties');

    // If element is new, set private by default.
    if (empty($element_properties['title'])) {
      $form['admin']['private']['#value'] = TRUE;
    }

    return $form;
  }
}
