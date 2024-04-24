<?php

namespace Drupal\os2forms_digital_post\Helper;

use DigitalPost\MeMo\Message;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_digital_post\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_digital_post\Model\Document;
use Drupal\os2forms_digital_post\Plugin\WebformHandler\WebformHandlerSF1601;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;
use ItkDev\Serviceplatformen\Service\SF1601\Serializer;
use Oio\Fjernprint\ForsendelseI;

/**
 * Abstract message helper.
 */
abstract class AbstractMessageHelper {

  /**
   * Constructor.
   */
  public function __construct(
    readonly protected Settings $settings,
    readonly protected ElementInfoManager $elementInfoManager,
    readonly protected WebformTokenManagerInterface $webformTokenManager
  ) {
  }

  /**
   * Get the main document.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\os2forms_digital_post\Exception\InvalidAttachmentElementException
   *
   * @see WebformAttachmentController::download()
   *
   * @phpstan-param array<string, mixed> $handlerSettings
   */
  protected function getMainDocument(WebformSubmissionInterface $submission, array $handlerSettings): Document {
    // Lifted from Drupal\webform_attachment\Controller\WebformAttachmentController::download.
    $element = $handlerSettings[WebformHandlerSF1601::MEMO_MESSAGE][WebformHandlerSF1601::ATTACHMENT_ELEMENT];
    $element = $submission->getWebform()->getElement($element) ?: [];
    [$type] = explode(':', $element['#type']);
    $instance = $this->elementInfoManager->createInstance($type);

    if (!$instance instanceof WebformAttachmentBase) {
      throw new InvalidAttachmentElementException(sprintf('Attachment element must be an instance of %s. Found %s.', WebformAttachmentBase::class, get_class($instance)));
    }

    $fileName = $instance::getFileName($element, $submission);
    $mimeType = $instance::getFileMimeType($element, $submission);
    $content = $instance::getFileContent($element, $submission);

    return new Document(
      $content,
      $mimeType,
      $fileName
    );
  }

  /**
   * Replace tokens.
   */
  protected function replaceTokens(string $text, WebformSubmissionInterface $submission): string {
    return $this->webformTokenManager->replace($text, $submission);
  }

  /**
   * Convert MeMo message to DOM document.
   */
  public function message2dom(Message|ForsendelseI $message): \DOMDocument {
    $document = new \DOMDocument();
    $document->loadXML((new Serializer())->serialize($message));

    return $document;
  }

}
