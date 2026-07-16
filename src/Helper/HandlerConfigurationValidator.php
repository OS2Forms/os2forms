<?php

namespace Drupal\os2forms\Helper;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms\Form\HandlerConfigurationValidationForm;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformInterface;

/**
 * Validates configuration of webform handlers.
 */
class HandlerConfigurationValidator {
  use StringTranslationTrait;

  /**
   * Constructs a HandlerConfigurationValidator object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    private readonly FormBuilderInterface $formBuilder,
    private readonly MessengerInterface $messenger,
  ) {
  }

  /**
   * Validates configuration of all handlers on a webform.
   *
   * Handlers are validated by programmatically submitting their
   * configuration form with the stored configuration.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return array
   *   Problems keyed by handler ID. Each value is an array with two keys:
   *   - label: the webform handler label
   *   - problems: a list of problems (strings or markup)
   *   Handlers without problems are not included.
   *
   * @phpstan-return array<string, mixed>
   */
  public function validate(WebformInterface $webform): array {
    $problems = [];
    foreach ($webform->getHandlers() as $handler) {
      $handlerProblems = $this->validateUsingConfigurationForm($handler);
      if ($handlerProblems) {
        $problems[$handler->getHandlerId()] = [
          'label' => $handler->label(),
          'problems' => $handlerProblems,
        ];
      }
    }

    return $problems;
  }

  /**
   * Validates handler configuration using the handler's configuration form.
   *
   * The configuration form is submitted programmatically with no input; form
   * elements then use their default values, i.e. the stored handler
   * configuration, and form validation reports stored values that are no
   * longer valid on the current webform, e.g. references to elements that
   * have been renamed or deleted (reported as illegal choices).
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   The webform handler.
   *
   * @return array
   *   A list of problems (strings or markup). An empty list means that the
   *   configuration is valid.
   *
   * @phpstan-return array<int, mixed>
   */
  private function validateUsingConfigurationForm(WebformHandlerInterface $handler): array {
    $formState = new FormState();
    // Building and validating the form may add messages, e.g. warnings from
    // the handler's own validation, that must not leak to the current page.
    // Remember any existing messages and restore them when done.
    $messages = $this->messenger->deleteAll();
    try {
      $this->formBuilder->submitForm(new HandlerConfigurationValidationForm($handler), $formState);
      $errors = $formState->getErrors();
    }
    catch (\Throwable $throwable) {
      $errors = [
        $this->t('Validating the configuration failed: @message', [
          '@message' => $throwable->getMessage(),
        ]),
      ];
    }
    finally {
      $this->messenger->deleteAll();
      foreach ($messages as $type => $typeMessages) {
        foreach ($typeMessages as $message) {
          $this->messenger->addMessage($message, $type, TRUE);
        }
      }
    }

    return array_values($errors);
  }

}
