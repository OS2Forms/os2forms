<?php

namespace Drupal\os2forms_audit\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Psr\Log\LogLevel;

/**
 * Stores entities in the database.
 *
 * @AuditLogger(
 *   id = "database",
 *   title = @Translation("Database logger"),
 *   description = @Translation("Store entity data in the database.")
 * )
 */
class DatabaseEntityWriter extends PluginBase implements AuditLoggerInterface {

  /**
   * {@inheritdoc}
   */
  public function write(EntityInterface $entity) {
    // Code to write the $entity data to a file as an audit entry.

    // Then log the action like this:
    \Drupal::logger('os2form_audit')->notice('Entity with ID @id is written.', ['@id' => $entity->id()]);
  }

}
