<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
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
  public function write(EntityInterface $entity): void {
    // Code to write the $entity data to a file as an audit entry.

    // Then log the action like this:
    \Drupal::logger('os2forms_audit')->info('Entity with ID @id is written.', ['@id' => $entity->id()]);
  }
}
