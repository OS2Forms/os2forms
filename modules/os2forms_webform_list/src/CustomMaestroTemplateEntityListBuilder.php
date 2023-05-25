<?php

namespace Drupal\os2forms_webform_list;

use Drupal\maestro\Controller\MaestroTemplateListBuilder;

/**
 * Defines a class to build a listing of maestro template entities.
 *
 * @see \Drupal\maestro\Entity\MaestroTemplate
 */
class CustomMaestroTemplateEntityListBuilder extends MaestroTemplateListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    // @phpstan-ignore-next-line
    $entities = $this->storage->loadMultipleOverrideFree($entity_ids);

    uasort($entities, [$this->entityType->getClass(), 'sort']);
    foreach ($entities as $entity_name => $entity) {
      $access = $entity->access('update');
      if (!$access) {
        unset($entities[$entity_name]);
      }
    }
    return $entities;
  }

}
