<?php

namespace Drupal\os2forms_permissions_by_term\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure os2web_permissions_by_term settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'os2web_permissions_by_term.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2web_permissions_by_term_settings';
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
    $options = [0 => t('None')];

    $userFields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');

    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($userFields as $field_key => $field) {
      // If fieldType is entity_reference, we only support taxonomy terms.
      if ($field->getType() == 'entity_reference' && $field->getSetting('target_type') == 'taxonomy_term') {
        $options[$field_key] = $field_key;
      }
    }

    $form['os2web_permissions_by_term_custom_field'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Permissions by term custom field'),
      '#description' => $this->t('The value of this custom field is mapped to Permission by term real field on hook_user_update().'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('os2web_permissions_by_term_custom_field'),
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
