<?php

namespace AuditLoggerInterface;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\os2forms_audit\Plugin\AuditLoggerInterface;

/**
 * Writes entities to a file.
 *
 * @AuditLogger(
 *   id = "file",
 *   title = @Translation("File logger"),
 *   description = @Translation("Writes entities to a file.")
 * )
 */
class FileEntityWriter extends PluginBase implements AuditLoggerInterface {

  /**
   * {@inheritdoc}
   */
  public function write(EntityInterface $entity) {
    // Code to write the entity to a file.
    // This is just a placeholder and won't write the data.
    file_put_contents('path_to_your_file.txt', serialize($entity));
  }

}
