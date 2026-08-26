<?php

namespace Drupal\os2forms_digital_signature\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Resolves the document that should be sent for digital signing.
 */
final class SigningDocumentResolver {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly WebformElementManagerInterface $elementManager,
  ) {}

  /**
   * Resolves the attachment for a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   *
   * @return array|null
   *   The resolved attachment or NULL when no attachment can be resolved.
   *
   * @throws \Exception
   */
  public function resolve(WebformSubmissionInterface $webformSubmission): ?array {
    $webform = $webformSubmission->getWebform();
    $attachments = NULL;

    // Prefer the dedicated upload element and fall back to a generated
    // OS2Forms attachment.
    $elementTypes = array_column($webform->getElementsDecodedAndFlattened(), '#type');
    $attachmentType = NULL;
    if (in_array('os2forms_digital_signature_document', $elementTypes, TRUE)) {
      $attachmentType = 'os2forms_digital_signature_document';
    }
    elseif (in_array('os2forms_attachment', $elementTypes, TRUE)) {
      $attachmentType = 'os2forms_attachment';
    }

    if (!$attachmentType) {
      return NULL;
    }

    $elements = $webform->getElementsInitializedAndFlattened();
    foreach ($webform->getElementsAttachments() as $elementAttachment) {
      $element = $elements[$elementAttachment];
      if (($element['#type'] ?? NULL) !== $attachmentType) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementAttachmentInterface $elementPlugin */
      $elementPlugin = $this->elementManager->getElementInstance($element);
      $attachments = $elementPlugin->getEmailAttachments($element, $webformSubmission);

      // Preserve the managed file ID so the signed document can replace the
      // upload that belongs to this submission.
      $fid = $webformSubmission->getElementData($elementAttachment);
      if ($fid && !empty($attachments)) {
        $attachments[0]['fid'] = $fid;
      }
      break;
    }

    if (empty($attachments)) {
      return NULL;
    }

    $attachment = reset($attachments);

    // SwiftMailer and Mime Mail consume filecontent rather than filepath.
    if (($this->moduleHandler->moduleExists('swiftmailer')
      || $this->moduleHandler->moduleExists('mimemail'))
      && isset($attachment['filecontent'], $attachment['filepath'])) {
      unset($attachment['filepath']);
    }

    return $attachment;
  }

}
