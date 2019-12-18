<?php

namespace Drupal\os2forms\Plugin\WebformElement;

use Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentBase;

/**
 * Provides a 'webform_attachment_os2forms_xml' element.
 *
 * @WebformElement(
 *   id = "webform_attachment_os2forms_xml",
 *   label = @Translation("Attachment OS2Forms XML"),
 *   description = @Translation("Generates an xml attachment file."),
 *   category = @Translation("File attachment elements"),
 * )
 */
class WebformAttachmentXml extends WebformAttachmentBase {
}
