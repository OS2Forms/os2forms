<?php

namespace Drupal\os2forms_encrypt\Helper;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form helper class.
 */
class FormHelper {

  /**
   * Removes 'element_encrypt' element from element forms.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form id.
   */
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id) {
    if ('webform_ui_element_form' === $form_id) {
      if (isset($form['element_encrypt'])) {
        unset($form['element_encrypt']);
      }
    }
  }

}
