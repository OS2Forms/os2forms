<?php

namespace Drupal\os2forms_nemid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure os2web_nemid settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'os2forms_nemid.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_nemid_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [SettingsForm::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Import settings.
    $form['os2forms_nemid_hide_active_nemid_session_message'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide message about active Nemid session, if it exists.'),
      '#description' => t('If checked, meessage aboout active NemID session will not shown on webform page'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('os2forms_nemid_hide_active_nemid_session_message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config(SettingsForm::$configName);
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
