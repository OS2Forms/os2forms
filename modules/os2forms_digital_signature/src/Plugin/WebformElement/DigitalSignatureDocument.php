<?php

namespace Drupal\os2forms_digital_signature\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\WebformSubmissionInterface;

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


  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);

    if (empty($file)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'basename':
      case 'extension':
      case 'data':
      case 'id':
      case 'mime':
      case 'name':
      case 'raw':
      case 'size':
      case 'url':
      case 'value':
        return $this->formatTextItem($element, $webform_submission, $options);

      case 'link':
        return [
          '#theme' => 'file_link',
          '#file' => $file,
        ];

      default:
        return [
          '#theme' => 'webform_element_document_file',
          '#element' => $element,
          '#value' => $value,
          '#options' => $options,
          '#file' => $file,
        ];
    }
  }

}
