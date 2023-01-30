<?php

namespace Drupal\os2forms_attachment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_attachment\AttachmentComponentInterface;

/**
 * Provides route responses for webform options.
 */
class AttachmentComponentController extends ControllerBase {

  /**
   * Attachment component edit route title callback.
   *
   * @param \Drupal\os2forms_attachment\AttachmentComponentInterface $os2forms_attachment_component
   *   The attachment component.
   *
   * @return string
   *   The attachment component label as a render array.
   */
  public function editTitle(AttachmentComponentInterface $os2forms_attachment_component) {
    return 'Edit ' . $os2forms_attachment_component->label();
  }

}
