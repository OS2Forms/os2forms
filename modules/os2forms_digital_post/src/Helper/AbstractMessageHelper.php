<?php

namespace Drupal\os2forms_digital_post\Helper;

use DigitalPost\MeMo\Message;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_digital_post\EventSubscriber\Os2formsDigitalPostSubscriber;
use Drupal\os2forms_digital_post\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_digital_post\Exception\InvalidAttachmentFileTypeException;
use Drupal\os2forms_digital_post\Exception\RuntimeException;
use Drupal\os2forms_digital_post\Model\Document;
use Drupal\os2forms_digital_post\Plugin\WebformHandler\WebformHandlerSF1601;
use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;
use ItkDev\Serviceplatformen\Service\SF1601\Serializer;
use Oio\Fjernprint\ForsendelseI;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Abstract message helper.
 */
abstract class AbstractMessageHelper {

  /**
   * Constructor.
   */
  public function __construct(
    readonly protected Settings $settings,
    #[Autowire(service: 'plugin.manager.element_info')]
    readonly protected ElementInfoManager $elementInfoManager,
    #[Autowire(service: 'webform.token_manager')]
    readonly protected WebformTokenManagerInterface $webformTokenManager,
    readonly protected Os2formsDigitalPostSubscriber $digitalPostSubscriber,
    #[Autowire(service: 'plugin.manager.webform.element')]
    readonly protected WebformElementManagerInterface $elementManager,
  ) {
  }

  /**
   * Get main document.
   *
   * @see WebformAttachmentController::download()
   *
   * @phpstan-param array<string, mixed> $handlerSettings
   */
  protected function getMainDocument(WebformSubmissionInterface $submission, array $handlerSettings, CprLookupResult|CompanyLookupResult $recipientData): Document {
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

    // The way to alter html generated from entities is through the
    // PrintEvents::POST_RENDER event. See:
    // @Drupal\entity_print\Renderer::generateHtml,
    // To indicate digital post context and get the necessary information,
    // we add a flag to the session.
    $senderAddress = $handlerSettings[WebformHandlerSF1601::MEMO_MESSAGE][WebformHandlerSF1601::SENDER_ADDRESS] ?? '';
    $this->digitalPostSubscriber->setDigitalPostContext($submission, $recipientData, $senderAddress);
    $content = $instance::getFileContent($element, $submission);
    $this->digitalPostSubscriber->deleteDigitalPostContext($submission);

    return new Document(
      $content,
      $mimeType,
      $fileName
    );
  }

  /**
   * Get attachment documents from the file elements selected on the handler.
   *
   * @phpstan-param array<string, mixed> $handlerSettings
   * @phpstan-return array<int, Document>
   */
  protected function getAttachmentDocuments(WebformSubmissionInterface $submission, array $handlerSettings): array {
    $documents = [];
    $webform = $submission->getWebform();
    $keys = $handlerSettings[WebformHandlerSF1601::MEMO_MESSAGE][WebformHandlerSF1601::ATTACHMENT_ELEMENTS] ?? [];
    foreach ($keys as $key) {
      $element = $webform->getElement($key);
      if (NULL === $element) {
        continue;
      }
      if (!in_array($element['#type'], WebformHandlerSF1601::FILE_ELEMENT_TYPES, TRUE)) {
        throw new InvalidAttachmentElementException(sprintf('Element %s (%s) is not a file element.', $key, $element['#type']));
      }
      /** @var \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase $plugin */
      $plugin = $this->elementManager->getElementInstance($element);

      foreach ($plugin->getTargetEntities($element, $submission) ?: [] as $file) {
        $filename = $file->getFilename();
        $mimeType = Document::getAttachmentMimeType($filename);
        if (NULL === $mimeType) {
          throw new InvalidAttachmentFileTypeException(sprintf('File %s (%s) from element %s is not an allowed Digital Post attachment type.', $filename, $file->getMimeType(), $key));
        }
        $content = file_get_contents($file->getFileUri());
        if (FALSE === $content) {
          throw new RuntimeException(sprintf('Cannot read file %s from element %s.', $filename, $key));
        }
        $documents[] = new Document($content, $mimeType, $filename);
      }
    }

    return $documents;
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
