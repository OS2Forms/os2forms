<?php

namespace Drupal\os2forms_digital_signature\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Digital post settings form.
 */
class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'os2forms_digital_signature.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_digital_signature_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['os2forms_digital_signature_remove_service_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signature server URL'),
      '#default_value' => $this->config(self::$configName)->get('os2forms_digital_signature_remove_service_url'),
      '#description' => $this->t('E.g. https://signering.bellcom.dk/sign.php?'),
    ];
    $form['os2forms_digital_signature_sign_hash_salt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hash Salt used for signature'),
      '#default_value' => $this->config(self::$configName)->get('os2forms_digital_signature_sign_hash_salt'),
      '#description' => $this->t('Must match hash salt on the signature server'),
    ];
    $form['os2forms_digital_signature_submission_allowed_ips'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List IPs which can download unsigned PDF submissions'),
      '#default_value' => $this->config(self::$configName)->get('os2forms_digital_signature_submission_allowed_ips'),
      '#description' => $this->t('Comma separated. e.g. 192.168.1.1,192.168.2.1'),
    ];
    $form['os2forms_digital_signature_submission_retention_period'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unsigned submission timespan (s)'),
      '#default_value' => ($this->config(self::$configName)->get('os2forms_digital_signature_submission_retention_period')) ?? 300,
      '#description' => $this->t('How many seconds can unsigned submission exist before being automatically deleted'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config(self::$configName);
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
