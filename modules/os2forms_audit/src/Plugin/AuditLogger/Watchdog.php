<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Core\Plugin\PluginBase;

/**
 * Stores entities in the database.
 *
 * @AuditLoggerProvider(
 *   id = "watchdog",
 *   title = @Translation("Watchdog"),
 *   description = @Translation("Store entity data in the database.")
 * )
 */
class Watchdog extends PluginBase implements AuditLoggerInterface {

  /**
   * {@inheritdoc}
   */
  public function log(string $type, int $timestamp, string $line, array $metadata = []): void {
    $data = '';
    array_walk($metadata, function ($val, $key) use (&$data) {
      $data .= " $key=\"$val\"";
    });

    \Drupal::logger('os2forms_audit')->info('%type: %line (%data)', [
      'type' => $type,
      'line' => $line,
      'data' => $data,
    ]);
  }

}
