<?php

namespace Drupal\os2forms_attachment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\os2forms_attachment\AttachmentComponentInterface;

/**
 * Defines the OS2forms attachment component.
 *
 * @ConfigEntityType(
 *   id = "os2forms_attachment_component",
 *   label = @Translation("OS2forms attachment component"),
 *   label_singular = @Translation("OS2forms attachment component"),
 *   label_plural = @Translation("OS2forms attachment components"),
 *   label_count = @PluralTranslation(
 *     singular = "@count component",
 *     plural = "@count components",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\os2forms_attachment\Controller\AttachmentComponentListBuilder",
 *     "form" = {
 *       "add" = "Drupal\os2forms_attachment\Form\AttachmentComponentForm",
 *       "edit" = "Drupal\os2forms_attachment\Form\AttachmentComponentForm",
 *       "delete" = "Drupal\os2forms_attachment\Form\AttachmentComponentDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/webform/config/os2forms_attachment_component/{os2forms_attachment_component}",
 *     "delete-form" = "/admin/structure/webform/config/os2forms_attachment_component/{os2forms_attachment_component}/delete",
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "body" = "body",
 *     "type" = "type",
 *   }
 * )
 */
class AttachmentComponent extends ConfigEntityBase implements AttachmentComponentInterface {

  /**
   * The component ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The component label.
   *
   * @var string
   */
  protected $label;

  /**
   * The component body.
   *
   * @var string
   */
  protected $body;

  /**
   * The component type.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

}
