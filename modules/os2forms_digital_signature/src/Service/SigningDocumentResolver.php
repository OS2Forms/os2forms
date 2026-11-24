<?php

namespace Drupal\os2forms_digital_signature\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;

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
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Loads or generates the attachment for a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   *
   * @return array|null
   *   The attachment or NULL when no attachment can be loaded or generated.
   *
   * @throws \Exception
   */
  public function loadAttachment(WebformSubmissionInterface $webformSubmission): ?array {
    $resolvedElement = $this->resolveElement($webformSubmission);
    if ($resolvedElement === NULL) {
      return NULL;
    }

    $elementKey = $resolvedElement['key'];
    $element = $resolvedElement['element'];

    /** @var \Drupal\webform\Plugin\WebformElementAttachmentInterface $elementPlugin */
    $elementPlugin = $this->elementManager->getElementInstance($element);
    $attachments = $elementPlugin->getEmailAttachments($element, $webformSubmission);

    if (empty($attachments)) {
      return NULL;
    }

    // Only an uploaded document has a managed file to replace.
    if (($element['#type'] ?? '') === 'os2forms_digital_signature_document'
      && ($fid = $webformSubmission->getElementData($elementKey))) {
      $attachments[0]['fid'] = $fid;
    }

    return reset($attachments);
  }

  /**
   * Resolves the configured signing element without loading attachment content.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   *
   * @return array|null
   *   The element key and definition, or NULL when configuration is invalid.
   */
  public function resolveElement(WebformSubmissionInterface $webformSubmission): ?array {
    $webform = $webformSubmission->getWebform();
    $elementKey = '';
    // The handler has single cardinality. Use its configured element for both
    // the signing request and callback; never accept a file ID from the URL.
    foreach ($webform->getHandlers('os2forms_digital_signature', TRUE) as $handler) {
      $elementKey = $handler->getConfiguration()['settings']['attachment_element'] ?? '';
      break;
    }

    if ($elementKey === '') {
      $this->logger->error('Digital signature handler has no attachment_element configured for webform %webform.', [
        '%webform' => $webform->id(),
      ]);
      return NULL;
    }

    $elements = $webform->getElementsInitializedAndFlattened();
    if (!isset($elements[$elementKey])) {
      $this->logger->error('Configured attachment element %element does not exist on webform %webform.', [
        '%element' => $elementKey,
        '%webform' => $webform->id(),
      ]);
      return NULL;
    }

    $element = $elements[$elementKey];
    if (!$this->isSignableElement($element)) {
      $this->logger->error('Configured attachment element %element on webform %webform does not provide a PDF and cannot be signed.', [
        '%element' => $elementKey,
        '%webform' => $webform->id(),
      ]);
      return NULL;
    }

    return [
      'key' => $elementKey,
      'element' => $element,
    ];
  }

  /**
   * Checks whether an element provides a PDF that can be signed.
   *
   * @param array $element
   *   The flattened webform element.
   *
   * @return bool
   *   TRUE if the element can be signed.
   */
  public function isSignableElement(array $element): bool {
    return match ($element['#type'] ?? '') {
      'os2forms_digital_signature_document' => TRUE,
      'os2forms_attachment' => ($element['#export_type'] ?? '') === 'pdf',
      default => FALSE,
    };
  }

}
