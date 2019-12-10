<?php

namespace Drupal\os2forms\Element;

use Drupal\webform_attachment\Element\WebformAttachmentInterface;

/**
 * Provides an interface for XML webform attachment element.
 */
interface WebformAttachmentXmlInterface extends WebformAttachmentInterface {

  /**
   * Get xml context.
   *
   * @return array
   *   Array with xml context. Available values:
   *    - xml_root_node_name
   *    - xml_type_cast_attributes
   *    - xml_format_output
   *    - xml_version
   *    - xml_encoding
   *    - xml_standalone
   */
  public static function getXmlContext();

}
