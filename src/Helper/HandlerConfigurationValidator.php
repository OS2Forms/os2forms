<?php

namespace Drupal\os2forms\Helper;

use Drupal\os2forms\Plugin\WebformHandler\OS2FormsHandlerInterface;
use Drupal\webform\WebformInterface;

/**
 * Validates configuration of webform handlers.
 */
class HandlerConfigurationValidator {

  /**
   * Validates configuration of all handlers on a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return array
   *   Problems keyed by handler ID. Each value is an array with two keys:
   *   - label: the webform handler label
   *   - problems: a list of problems (translatable markup)
   *   Handlers without problems are not included.
   *
   * @phpstan-return array<string, mixed>
   */
  public function validate(WebformInterface $webform): array {
    $problems = [];
    foreach ($webform->getHandlers() as $handler) {
      if ($handler instanceof OS2FormsHandlerInterface) {
        if ($handlerProblems = $handler->validateConfiguration()) {
          $problems[$handler->getHandlerId()] = [
            'label' => $handler->label(),
            'problems' => $handlerProblems,
          ];
        }
      }
    }

    return $problems;
  }

}
