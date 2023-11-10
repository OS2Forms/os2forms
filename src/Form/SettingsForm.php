<?php

namespace Drupal\os2forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformThirdPartySettingsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure os2forms settings for this site.
 */
class SettingsForm extends FormBase {

  /**
   * Third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $thirdPartySettingsManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_settings';
  }

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $thirdPartySettingsManager
   *   Third party settings manager.
   */
  public function __construct(WebformThirdPartySettingsManagerInterface $thirdPartySettingsManager) {
    $this->thirdPartySettingsManager = $thirdPartySettingsManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.third_party_settings_manager')
    );
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

    $third_party_settings = $form_state->getValue('third_party_settings');
    foreach ($third_party_settings as $module_key => $settings) {
      foreach ($settings as $settingKey => $settingValues) {
        $savedSettings = $this->thirdPartySettingsManager->getThirdPartySetting($module_key, $settingKey);
        if (is_array($settingValues)) {
          $savedSettings = array_replace($savedSettings, $settingValues);
        }
        else {
          $savedSettings = $settingValues;
        }

        $this->thirdPartySettingsManager->setThirdPartySetting($module_key, $settingKey, $savedSettings);
      }
    }

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));

  }

}
