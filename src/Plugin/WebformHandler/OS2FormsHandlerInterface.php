<?php

namespace Drupal\os2forms\Plugin\WebformHandler;

/**
 * Interface for webform handlers that can validate their configuration.
 */
interface OS2FormsHandlerInterface {

  /**
   * Validates the handler configuration against the current webform.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   A list of problems with the handler configuration. An empty list means
   *   that the configuration is valid.
   *
   * @phpstan-return array<int, mixed>
   */
  public function validateConfiguration(): array;

}
