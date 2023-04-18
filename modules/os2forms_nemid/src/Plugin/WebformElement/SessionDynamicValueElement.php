<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a 'os2forms_autocomplete' element.
 *
 * @WebformElement(
 *   id = "os2forms_session_dynamic_value",
 *   label = @Translation("OS2Forms Session dynamic value field"),
 *   description = @Translation("Provides OS2session dynamic value element."),
 *   category = @Translation("OS2Forms"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidUuid
 */
class SessionDynamicValueElement extends NemloginElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();

    // Adding prepopulate_key properties.
    $properties['prepopulate_key'] = '';

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    $prepopulateKey = str_replace('[', ',', $element['#prepopulate_key']);
    $prepopulateKey = str_replace(']', '', $prepopulateKey);

    $prepopulateKeyArray = explode(',', $prepopulateKey);

    // If array has just one element, return it.
    if (count($prepopulateKeyArray) == 1) {
      return $prepopulateKeyArray[0];
    }
    else {
      return $prepopulateKeyArray;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform_ui\Form\WebformUiElementEditForm $webformSubmissionForm */
    $webformElementEditForm = $form_state->getFormObject();

    // Getting webform type settings.
    $webform = $webformElementEditForm->getWebform();
    $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // Getting auth plugin ID override.
    $authPluginId = NULL;
    if (isset($webformNemidSettings['session_type']) && !empty($webformNemidSettings['session_type'])) {
      $authPluginId = $webformNemidSettings['session_type'];
    }
    else {
      /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
      $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
      $authPluginId = $authProviderService->getActivePluginId();
    }

    $form['element']['prepopulate_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OS2Forms session prepopulate key'),
      '#description' => $this->t('Value will be attempted to be fetched from session using this key. Use the following notation for arrays: department[0] or department[0][title]'),
      '#suffix' => $this->t('Login here to see available values: <a target="_blank" href="@link">login</a>', ['@link' => Url::fromRoute('os2web_nemlogin.test', ['plugin_id' => $authPluginId])->toString()]),
    ];

    return $form;
  }

}
