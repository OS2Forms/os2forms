<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a abstract NemID Element.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class NemidElementBase extends WebformElementBase implements NemloginPrepopulateFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    // Here you define your webform element's default properties,
    // which can be inherited.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::getDefaultProperties
    // @see \Drupal\webform\Plugin\WebformElementBase::getDefaultBaseProperties
    $properties = parent::getDefaultProperties() + [
      'multiple' => '',
      'size' => '',
      'minlength' => '',
      'maxlength' => '',
      'placeholder' => '',
    ];

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Only manipulate element on actual submission page.
    if (!$webform_submission->isCompleted()) {
      // Getting webform type settings.
      $webform = $webform_submission->getWebform();
      $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');
      $webform_type = NULL;

      // If webform type is set, handle element visiblity.
      if (isset($webformNemidSettings['webform_type'])) {
        $webform_type = $webformNemidSettings['webform_type'];

        $this->handleElementVisibility($element, $webform_type);
      }

      /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
      $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');
      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
      $plugin = $authProviderService->getActivePlugin();

      if ($plugin->isAuthenticated()) {
        // Handle fields visibility depending on Authorization type.
        if ($plugin->isAuthenticatedPerson()) {
          $this->handleElementVisibility($element, OS2FORMS_NEMID_WEBFORM_TYPE_PERSONAL);
        }
        if ($plugin->isAuthenticatedCompany()) {
          $this->handleElementVisibility($element, OS2FORMS_NEMID_WEBFORM_TYPE_COMPANY);
        }

        // TODO: make a proper values fetching with Serviceplatformen.
        $nemloginFieldKey = $this->getNemloginFieldKey();
        $value = $plugin->fetchValue($nemloginFieldKey);
        $element['#default_value'] = $value;
      }

    }

    parent::prepare($element, $webform_submission);

    // Here you can customize the webform element's properties.
    // You can also customize the form/render element's properties via the
    // FormElement.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
    $element_properties = $form_state->get('element_properties');
    // If element is new, set disabled by default.
    if (empty($element_properties['title'])) {
      $form['form']['disabled']['#value'] = TRUE;
    }

    // Here you can define and alter a webform element's properties UI.
    // Form element property visibility and default values are defined via
    // ::getDefaultProperties.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::form
    // @see \Drupal\webform\Plugin\WebformElement\TextBase::form
    return $form;
  }

  /**
   * Handles element visibility on the webform.
   *
   * If element type is not corresponding with the form type, element if hidden.
   *
   * @param array $element
   *   Array element info.
   * @param string $allowed_type
   *   Allowed type of the element.
   */
  protected function handleElementVisibility(array &$element, $allowed_type) {
    if ($allowed_type === OS2FORMS_NEMID_WEBFORM_TYPE_PERSONAL) {
      if ($this instanceof NemidElementCompanyInterface) {
        $element['#access'] = FALSE;
      }
    }
    elseif ($allowed_type === OS2FORMS_NEMID_WEBFORM_TYPE_COMPANY) {
      if ($this instanceof NemidElementPersonalInterface) {
        $element['#access'] = FALSE;
      }
    }
  }

}
