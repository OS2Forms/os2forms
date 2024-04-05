<?php

namespace Drupal\os2forms_encrypt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class Os2FormsEncryptAdminForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The name of the configuration setting.
   *
   * @var string
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

    $link = Link::createFromRoute($this->t('administration'), 'entity.key.collection');
    $form['notice'] = [
      '#type' => 'inline_template',
      '#template' => '<h3>{{ title }}</h3><p>{{ message|t }}</p><p>{{ adminMessage|t }}</p>',
      '#context' => [
        'title' => 'Please note',
        'message' => 'The encryption key that comes with this module should <strong>not</strong> be used and should be changed before encrypting anything.',
        'adminMessage' => 'You can modify the key (named "webform") in the keys ' . $link->toString() . ' panel. Additionally, the execution of this command can generate a new 256-bit key for you: <pre>dd if=/dev/urandom bs=32 count=1 | base64 -i -</pre>',
      ],
    ];

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
