<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for AuditLogger plugins.
 */
interface AuditLoggerInterface extends PluginInspectionInterface {

  /**
   * Logs a message with optional metadata.
   *
   * @param int $timestamp
   *   The timestamp of the log entry.
   * @param string $line
   *   The log message.
   * @param array $metadata
   *   Additional metadata associated with the log entry. Defaults to an empty
   *   array.
   */
  public function log(int $timestamp, string $line, array $metadata = []): void;

}
