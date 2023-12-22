<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Select;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_nemid_children_select' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_children_select",
 *   label = @Translation("MitID Children Select"),
 *   description = @Translation("Provides a MitID Children select element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidChildrenRadios
 */
class NemidChildrenSelect extends Select implements NemidElementPersonalInterface, NemidPrepopulateFieldInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'options' => [],
    ] + parent::defineDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['options']['options']['#required'] = FALSE;
    $form['options']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorSourceValues(array $element) {
    // Setting empty options to avoid errors during load.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Setting empty options to avoid errors during load.
    $element['#options'] = [];
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionForm $webformSubmissionForm */
    $webformSubmissionForm = $form_state->getFormObject();

    /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
    $webformSubmission = $webformSubmissionForm->getEntity();

    // Only manipulate element on actual submission page.
    if (!$webformSubmission->isCompleted()) {
      // Getting webform type settings.
      $webform = $webformSubmission->getWebform();
      $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

      // If webform type is set, handle element visiblity.
      if (isset($webformNemidSettings['webform_type'])) {
        $webform_type = $webformNemidSettings['webform_type'];
        if ($webform_type == NemidElementBase::WEBFORM_TYPE_COMPANY) {
          $element['#access'] = FALSE;
        }
      }

      // Getting auth plugin ID override.
      $authPluginId = NULL;
      if (isset($webformNemidSettings['session_type']) && !empty($webformNemidSettings['session_type'])) {
        $authPluginId = $webformNemidSettings['session_type'];
      }

      /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
      $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
      $authProviderPlugin = ($authPluginId) ? $authProviderService->getPluginInstance($authPluginId) : $authProviderService->getActivePlugin();

      // Handle fields visibility depending on Authorization type.
      if ($authProviderPlugin->isAuthenticated()) {
        if ($authProviderPlugin->isAuthenticatedCompany()) {
          $element['#access'] = FALSE;
        }
      }

      // Handle element prepopulate.
      $this->handleElementPrepopulate($element, $form_state);

      NestedArray::setValue($form['elements'], $element['#webform_parents'], $element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return 'children';
  }

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
    /** @var \Drupal\os2forms_nemid\Service\FormsHelper $formsHelper */
    $formsHelper = \Drupal::service('os2forms_nemid.forms_helper');
    $cprLookupResult = $formsHelper->retrieveCprLookupResult($form_state);

    $options = [];

    if ($cprLookupResult) {
      $prepopulateKey = $this->getPrepopulateFieldFieldKey($element);
      if ($children = $cprLookupResult->getFieldValue($prepopulateKey)) {
        if (is_array($children) && !empty($children)) {
          foreach ($children as $child) {
            $options[$child['cpr']] = $child['name'];
          }
        }
      }
    }

    $element['#options'] = $options;
  }

}
