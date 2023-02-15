<?php

namespace Drupal\os2forms_attachment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Attachment Component entity.
 */
interface AttachmentComponentInterface extends ConfigEntityInterface {

  /**
   * Get component body.
   *
   * @return string
   *   HTML component body.
   */
  public function getBody();

  /**
   * Get component type.
   *
   * @return string
   *   Component type.
   */
  public function getType();

}
