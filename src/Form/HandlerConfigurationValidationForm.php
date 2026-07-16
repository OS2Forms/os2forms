<?php

namespace Drupal\os2forms\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Form used for programmatically validating webform handler configurations.
 *
 * Building the form populates all elements with their default values, i.e.
 * the stored handler configuration, and submitting the form programmatically
 * with no input validates the configuration against the current webform
 * state (cf. \Drupal\Core\Form\FormBuilder::submitForm()).
 *
 * The real handler edit form cannot be used for this as submitting
 * \Drupal\webform\Form\WebformHandlerFormBase updates and saves the webform.
 *
 * @see \Drupal\os2forms\Helper\HandlerConfigurationValidator
 */
class HandlerConfigurationValidationForm implements FormInterface {

  /**
   * Constructs a HandlerConfigurationValidationForm.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   The webform handler whose configuration to validate.
   */
  public function __construct(
    private readonly WebformHandlerInterface $handler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_handler_configuration_validation_form';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build the handler configuration form the same way as
    // \Drupal\webform\Form\WebformHandlerFormBase::form().
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->handler->buildConfigurationForm($form['settings'], $subform_state);
    $form['settings']['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->handler->validateConfigurationForm($form, $subform_state);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty: this form is only used for validation.
  }

}
