<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Writes entities to a file.
 *
 * @AuditLoggerProvider(
 *   id = "file",
 *   title = @Translation("File logger"),
 *   description = @Translation("Writes entities to a file.")
 * )
 */
class File extends PluginBase implements AuditLoggerInterface {

  /**
   * {@inheritdoc}
   */
  public function write(EntityInterface $entity): void {
    // Code to write the entity to a file.
    // This is just a placeholder and won't write the data.
    file_put_contents('path_to_your_file.txt', serialize($entity));
  }

}
