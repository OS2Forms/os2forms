<?php

namespace Drupal\os2forms_audit\Service;

use Drupal\os2forms_audit\LoggerManager;

/**
 * Class Logger
 *
 * Helper service to send log messages in the right direction.
 */
class Logger {

  public function __construct(
    private readonly LoggerManager $loggerManager
  ) {
  }

  public function log() {
    $this->loggerManager->getDefinitions();

  }
}
