<?php

namespace Drupal\os2forms\Element;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;

/**
 * Abstract 'webform_attachment_os2forms_xml' element.
 */
abstract class WebformAttachmentXml extends WebformAttachmentBase implements WebformAttachmentXmlInterface {

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    return \Drupal::service('serializer')->serialize($webform_submission->getData(), 'xml', self::getXmlContext());
  }

  /**
   * {@inheritdoc}
   */
  public static function getXmlContext() {
    return [
      'xml_root_node_name' => 'webformSubmission',
      'xml_encoding' => 'UTF-8',
    ];
  }

}
