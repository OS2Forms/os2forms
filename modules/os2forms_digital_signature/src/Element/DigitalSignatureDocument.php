<?php

namespace Drupal\os2forms_digital_signature\Element;


use Drupal\webform\Element\WebformManagedFileBase;

/**
 * Provides a webform element for an 'os2forms_digital_signature_document' element.
 *
 * @FormElement("os2forms_digital_signature_document")
 */
class DigitalSignatureDocument extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'application/pdf';

}
