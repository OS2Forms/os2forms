<?php

namespace Drupal\os2forms_audit\Commands;

use Drupal\os2forms_audit\Service\Logger;
use Drush\Commands\DrushCommands;

/**
 * Simple command to send log message into audit log.
 */
class AuditLogDrushCommands extends DrushCommands {

  /**
   * Os2FormsAuditDrushCommands constructor.
   *
   * @param \Drupal\os2forms_audit\Service\Logger $auditLogger
   *   Audit logger service.
   */
  public function __construct(
    protected readonly Logger $auditLogger,
  ) {
    parent::__construct();
  }

  /**
   * Log a test message to the os2forms_audit logger.
   *
   * @param string $log_message
   *   Message to be logged.
   *
   * @command os2forms_audit:log
   * @usage os2forms_audit:log 'This is a test message.'
   *   Logs 'This is a test message.' to the os2forms_audit logger.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function logMessage(string $log_message = ''): void {
    if (empty($log_message)) {
      throw new \Exception('Log message cannot be empty.');
    }
    $this->auditLogger->log('test', time(), $log_message, ['from' => 'drush']);
  }

}
