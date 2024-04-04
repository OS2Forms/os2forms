<?php

namespace Drupal\os2forms_encrypt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Os2FormsEncryptAdminForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var string $configName
   */
  public static string $configName = 'os2forms_encrypt.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2forms_encrypt_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('os2forms_encrypt.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled encryption'),
      '#description' => $this->t('Enable encryption for all webform fields. Please note that encryption will be applied only to those fields that are modified after enabling this option.'),
      '#default_value' => $config->get('enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config(self::$configName)
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();
  }

}
