<?php

namespace Drupal\os2forms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\EntitySettings\WebformEntitySettingsBaseForm;

/**
 * Webform OS2forms settings.
 */
class WebformSettingsForm extends WebformEntitySettingsBaseForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['third_party_settings']['#tree'] = TRUE;

    return parent::form($form, $form_state);
  }

}
