<?php

namespace Drupal\os2forms_audit\Plugin;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for AuditLogger plugins.
 */
interface AuditLoggerInterface {

  /**
   * Write the entity data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be audited.
   */
  public function write(EntityInterface $entity);
}
