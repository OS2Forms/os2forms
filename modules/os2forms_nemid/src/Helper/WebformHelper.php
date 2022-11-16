<?php

namespace Drupal\os2forms_nemid\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_nemid\Plugin\WebformElement\NemidElementBase;

/**
 * Webform helper.
 */
class WebformHelper {
  use StringTranslationTrait;

  /**
   * Implements hook_webform_third_party_settings_form_alter().
   */
  public function webformThirdPartySettingsFormAlter(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getEntity();
    $settings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // OS2Forms NemID.
    $form['third_party_settings']['os2forms']['os2forms_nemid'] = [
      '#type' => 'details',
      '#title' => $this->t('OS2Forms NemID settings'),
      '#open' => TRUE,
    ];

    // Webform type.
    $webform_types = [
      NemidElementBase::WEBFORM_TYPE_PERSONAL => $this->t('Personal'),
      NemidElementBase::WEBFORM_TYPE_COMPANY => $this->t('Company'),
    ];
    $form['third_party_settings']['os2forms']['os2forms_nemid']['webform_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform type'),
      '#default_value' => !(empty($settings)) ? $settings['webform_type'] : NULL,
      '#empty_option' => $this->t('Not specified'),
      '#options' => $webform_types,
      '#description' => $this->t('Based on the selected type form irrelevant fields will not be shown to the end user'),
    ];

    // Nemlogin auto redirect.
    $form['third_party_settings']['os2forms']['os2forms_nemid']['nemlogin_auto_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect to nemlogin automatically'),
      '#default_value' => !(empty($settings)) ? $settings['nemlogin_auto_redirect'] : FALSE,
      '#description' => $this->t('Redirection will happen right after user has is accessing the form, if user is already authenticated via NemID, redirection will not happen.'),
    ];
  }

}
