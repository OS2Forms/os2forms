<?php

namespace Drupal\os2forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure os2forms settings for this site.
 */
class SettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['third_party_settings']['#tree'] = TRUE;

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
    $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

    $third_party_settings = $form_state->getValue('third_party_settings');
    foreach ($third_party_settings as $module_key => $settings) {
      foreach ($settings as $settingKey => $settingValues) {
        $savedSettings = $third_party_settings_manager->getThirdPartySetting($module_key, $settingKey);
        if (is_array($settingValues)) {
          $savedSettings = array_replace($savedSettings, $settingValues);
        }
        else {
          $savedSettings = $settingValues;
        }

        $third_party_settings_manager->setThirdPartySetting($module_key, $settingKey, $savedSettings);
      }
    }

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

}
