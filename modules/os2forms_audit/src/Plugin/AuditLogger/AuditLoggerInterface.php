<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for AuditLogger plugins.
 */
interface AuditLoggerInterface extends PluginInspectionInterface {

  /**
   * Write the entity data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be audited.
   */
  public function write(EntityInterface $entity);

}
