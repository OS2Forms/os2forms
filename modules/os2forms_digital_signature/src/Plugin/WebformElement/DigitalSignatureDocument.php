<?php

namespace Drupal\os2forms_digital_signature\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;

/**
 * Provides a 'os2forms_digital_signature_document' element.
 *
 * @WebformElement(
 *   id = "os2forms_digital_signature_document",
 *   label = @Translation("OS2forms digital signature document"),
 *   description = @Translation("Upload PDF file for digital signature."),
 *   category = @Translation("OS2Forms"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class DigitalSignatureDocument extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();
    $formats['file'] = $this->t('PDF file for signature');
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileExtensions(array $element = NULL) {
    return 'pdf';
  }

}
