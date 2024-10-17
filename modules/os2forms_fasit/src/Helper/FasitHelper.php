<?php

namespace Drupal\os2forms_fasit\Helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\os2forms_attachment\Element\AttachmentElement;
use Drupal\os2forms_fasit\Exception\FasitResponseException;
use Drupal\os2forms_fasit\Exception\FasitXMLGenerationException;
use Drupal\os2forms_fasit\Exception\FileTypeException;
use Drupal\os2forms_fasit\Exception\InvalidSettingException;
use Drupal\os2forms_fasit\Exception\InvalidSubmissionException;
use Drupal\os2forms_fasit\Plugin\WebformHandler\FasitWebformHandler;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Fasit helper class.
 */
class FasitHelper {
  private const FASIT_API_METHOD_UPLOAD = 'upload2';
  private const FASIT_API_METHOD_CREATE = 'oio/3.0.0/opret';
  private const FASIT_API_PRIMAERPART_TEMPLATE_VALUE = 'urn:schultz:dokument:1.0:primaerpart:cpr:';

  /**
   * File element types that may contain PDF files.
   */
  private const FILE_ELEMENT_TYPES = [
    'webform_document_file',
    'managed_file',
  ];

  public function __construct(private readonly ClientInterface $client, private readonly EntityTypeManagerInterface $entityTypeManager, private readonly Settings $settings, private readonly CertificateLocatorHelper $certificateLocator) {
  }

  /**
   * Process submission.
   *
   * @param string $submissionId
   *   The submission id.
   * @param array $handlerConfiguration
   *   The Fasit handler configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   * @throws \Drupal\os2forms_fasit\Exception\FasitResponseException
   * @throws \Drupal\os2forms_fasit\Exception\FasitXMLGenerationException
   * @throws \Drupal\os2forms_fasit\Exception\InvalidSettingException
   * @throws \Drupal\os2forms_fasit\Exception\FileTypeException
   *
   * @phpstan-param array<string, mixed> $handlerConfiguration
   */
  public function process(string $submissionId, array $handlerConfiguration): void {
    $uploads = $this->uploadFiles($submissionId, $handlerConfiguration);
    $this->uploadDocument($uploads, $submissionId, $handlerConfiguration);
  }

  /**
   * Uploads files to Fasit.
   *
   * @param string $submissionId
   *   The submission id.
   * @param array $handlerConfiguration
   *   The Fasit handler configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   * @throws \Drupal\os2forms_fasit\Exception\FasitResponseException
   * @throws \Drupal\os2forms_fasit\Exception\FileTypeException
   * @throws \Drupal\os2forms_fasit\Exception\InvalidSettingException
   *
   * @phpstan-param array<string, mixed> $handlerConfiguration
   * @phpstan-return array<string, mixed>
   */
  private function uploadFiles(string $submissionId, array $handlerConfiguration): array {
    $uploads = [];

    // Handle attachment.
    $uploads[] = $this->uploadAttachment($submissionId, $handlerConfiguration);
    // Handle potential file elements.
    $fileElementsUpload = $this->uploadFileElements($submissionId);

    return array_merge($uploads, $fileElementsUpload);
  }

  /**
   * Uploads document containing uploaded files to Fasit.
   *
   * @param array $uploads
   *   The uploaded files.
   * @param string $submissionId
   *   The submission id.
   * @param array $handlerConfiguration
   *   The Fasit handler configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   * @throws \Drupal\os2forms_fasit\Exception\FasitResponseException
   * @throws \Drupal\os2forms_fasit\Exception\FasitXMLGenerationException
   * @throws \Drupal\os2forms_fasit\Exception\InvalidSettingException
   *
   * @phpstan-param array<string, mixed> $uploads
   * @phpstan-param array<string, mixed> $handlerConfiguration
   */
  private function uploadDocument(array $uploads, string $submissionId, array $handlerConfiguration): void {
    $endpoint = sprintf('%s/%s/%s/documents/%s',
      $this->settings->getFasitApiBaseUrl(),
      $this->settings->getFasitApiTenant(),
      $this->settings->getFasitApiVersion(),
      self::FASIT_API_METHOD_CREATE
    );

    // Check handler configuration.
    $this->checkHandlerConfiguration($handlerConfiguration, FasitWebformHandler::FASIT_HANDLER_CPR_ELEMENT);
    $this->checkHandlerConfiguration($handlerConfiguration, FasitWebformHandler::FASIT_HANDLER_DOCUMENT_TITLE);
    $this->checkHandlerConfiguration($handlerConfiguration, FasitWebformHandler::FASIT_HANDLER_DOCUMENT_DESCRIPTION);

    $fasitDocumentTitle = $handlerConfiguration[FasitWebformHandler::FASIT_HANDLER_GENERAL][FasitWebformHandler::FASIT_HANDLER_DOCUMENT_TITLE];
    $fasitDocumentDescription = $handlerConfiguration[FasitWebformHandler::FASIT_HANDLER_GENERAL][FasitWebformHandler::FASIT_HANDLER_DOCUMENT_DESCRIPTION];

    // Fasit CPR element.
    $webformCprElementId = $handlerConfiguration[FasitWebformHandler::FASIT_HANDLER_GENERAL][FasitWebformHandler::FASIT_HANDLER_CPR_ELEMENT];

    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);
    $submissionData = $submission->getData();

    $fasitCpr = $submissionData[$webformCprElementId] ?? NULL;

    // Fix if os2forms_person_lookup (cpr & name validation) element is used.
    if (is_array($fasitCpr)) {
      // Example:
      // [
      // 'cpr_number' => 1234567890,
      // 'name' => Eksempel Eksempelsen,
      // ].
      $fasitCpr = $fasitCpr['cpr_number'] ?? NULL;
    }

    if (NULL === $fasitCpr) {
      throw new InvalidSubmissionException(sprintf('Could not determine value of configured CPR element in submission.'));
    }

    // Construct XML.
    $doc = new \DOMDocument();

    if (!$doc->load(__DIR__ . '/templateCreateDocument.xml')) {
      throw new FasitXMLGenerationException('Could not load template XML');
    }

    // Set Document values.
    $doc->getElementsByTagName('BeskrivelseTekst')->item(0)->nodeValue = $fasitDocumentDescription;
    $doc->getElementsByTagName('TitelTekst')->item(0)->nodeValue = $fasitDocumentTitle;
    $doc->getElementsByTagName('BrevDato')->item(0)->nodeValue = (new \DateTimeImmutable())->format('Y-m-d');

    // Handle uploads
    // Use existing DelEgenskaber as template for each upload.
    // DelEgenskaber is the first child of Del.
    $delEgenskaber = $doc->getElementsByTagName('DelEgenskaber')->item(0);
    $del = $doc->getElementsByTagName('Del')->item(0);

    // Append a DelEgenskaber per upload.
    foreach ($uploads as $upload) {
      $copyDelEgenskaber = $delEgenskaber->cloneNode(TRUE);
      $copyChildNodes = $copyDelEgenskaber->childNodes;
      foreach ($copyChildNodes as $childNode) {
        if ($childNode->nodeName === 'DelTekst') {
          $childNode->nodeValue = $upload['filename'];
        }
        elseif ($childNode->nodeName === 'IndholdTekst') {
          $childNode->nodeValue = $upload['id'];
        }
      }

      $del->insertBefore($copyDelEgenskaber, $delEgenskaber);
    }

    // Remove template 'DelEgenskaber'.
    $parent = $delEgenskaber->parentNode;
    $parent->removeChild($delEgenskaber);

    // Handle Parter.
    $elements = $doc->getElementsByTagName('Parter')->item(0)->childNodes;

    foreach ($elements as $element) {
      if ($element->nodeName === 'ReferenceID') {
        $referenceIdChildElements = $element->childNodes;
        foreach ($referenceIdChildElements as $referenceIdChildElement) {
          if ($referenceIdChildElement->nodeName === 'UUIDIdentifikator') {
            $referenceIdChildElement->nodeValue = self::FASIT_API_PRIMAERPART_TEMPLATE_VALUE . $fasitCpr;
          }
        }
      }
    }

    [$certificateOptions, $tempCertFilename] = $this->getCertificateOptionsAndTempCertFilename();

    $options = [
      'headers' => [
        'Content-Type' => 'application/xml',
      ],
      'body' => $doc->saveXML(),
      'cert' => $certificateOptions,
    ];

    // Attempt upload.
    try {
      $response = $this->client->request('POST', $endpoint, $options);
    }
    catch (GuzzleException $e) {
      throw new FasitResponseException($e->getMessage(), $e->getCode());
    } finally {
      // Remove the certificate from disk.
      if (file_exists($tempCertFilename)) {
        unlink($tempCertFilename);
      }
    }

    if (Response::HTTP_OK !== $response->getStatusCode()) {
      throw new FasitResponseException(sprintf('Expected status code 200, received %d', $response->getStatusCode()));
    }
  }

  /**
   * Checks that a setting exists in configuration.
   *
   * @param array $handlerConfiguration
   *   The Fasit handler configuration.
   * @param string $setting
   *   The setting.
   *
   * @throws \Drupal\os2forms_fasit\Exception\InvalidSettingException
   *   Invalid setting exception.
   *
   * @phpstan-param array<string, mixed> $handlerConfiguration
   */
  private function checkHandlerConfiguration(array $handlerConfiguration, string $setting): void {
    if (!isset($handlerConfiguration[FasitWebformHandler::FASIT_HANDLER_GENERAL][$setting])) {
      throw new InvalidSettingException('Handler settings does not contain configuration of ' . str_replace('_', ' ', $setting));
    }
  }

  /**
   * Gets certificate options and temp certificate filename.
   *
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   *   Certificate locator exception.
   *
   * @phpstan-return array<mixed, mixed>
   */
  private function getCertificateOptionsAndTempCertFilename(): array {
    $certificateLocator = $this->certificateLocator->getCertificateLocator();
    $localCertFilename = tempnam(sys_get_temp_dir(), 'cert');
    file_put_contents($localCertFilename, $certificateLocator->getCertificate());
    $certificateOptions =
      $certificateLocator->hasPassphrase() ?
        [$localCertFilename, $certificateLocator->getPassphrase()]
        : $localCertFilename;

    return [$certificateOptions, $localCertFilename];
  }

  /**
   * Uploads attachment to Fasit.
   *
   * @param string $submissionId
   *   The submission id.
   * @param array $handlerConfiguration
   *   The Fasit handler configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   * @throws \Drupal\os2forms_fasit\Exception\FasitResponseException
   * @throws \Drupal\os2forms_fasit\Exception\InvalidSettingException
   *
   * @phpstan-param array<string, mixed> $handlerConfiguration
   * @phpstan-return array<string, mixed>
   */
  private function uploadAttachment(string $submissionId, array $handlerConfiguration): array {
    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);

    $this->checkHandlerConfiguration($handlerConfiguration, FasitWebformHandler::FASIT_HANDLER_ATTACHMENT_ELEMENT);

    $webformAttachmentElementId = $handlerConfiguration[FasitWebformHandler::FASIT_HANDLER_GENERAL][FasitWebformHandler::FASIT_HANDLER_ATTACHMENT_ELEMENT];
    $webformAttachmentElement = $submission->getWebform()->getElement($webformAttachmentElementId);

    if ('pdf' !== $webformAttachmentElement['#export_type']) {
      throw new InvalidSettingException(sprintf('Export type of attachment element (%s) must be pdf, found %s', $webformAttachmentElementId, $webformAttachmentElement['#export_type']));
    }

    $fileContent = AttachmentElement::getFileContent($webformAttachmentElement, $submission);
    $fileName = AttachmentElement::getFileName($webformAttachmentElement, $submission);
    $tempAttachmentFilename = tempnam(sys_get_temp_dir(), 'attachment');
    file_put_contents($tempAttachmentFilename, $fileContent);

    return $this->uploadFile($fileName, $tempAttachmentFilename);
  }

  /**
   * Uploads file to Fasit.
   *
   * @param string $originalFilename
   *   The original filename.
   * @param string $tempFilename
   *   The temp filename.
   *
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   *   Certificate locator exception.
   * @throws \Drupal\os2forms_fasit\Exception\FasitResponseException
   *   Fasit response exception.
   *
   * @phpstan-return array<string, mixed>
   */
  private function uploadFile(string $originalFilename, string $tempFilename): array {
    $endpoint = sprintf('%s/%s/%s/documents/%s',
      $this->settings->getFasitApiBaseUrl(),
      $this->settings->getFasitApiTenant(),
      $this->settings->getFasitApiVersion(),
      self::FASIT_API_METHOD_UPLOAD
    );

    [$certificateOptions, $tempCertFilename] = $this->getCertificateOptionsAndTempCertFilename();

    // Attempt upload.
    try {
      $options = [
        'headers' => [
          'Content-Type' => 'application/pdf',
          'X-Filename' => $originalFilename,
          'X-Title' => pathinfo($originalFilename, PATHINFO_FILENAME),
        ],
        'body' => Utils::tryFopen($tempFilename, 'r'),
        'cert' => $certificateOptions,
      ];

      $response = $this->client->request('POST', $endpoint, $options);
    }
    catch (GuzzleException $e) {
      throw new FasitResponseException($e->getMessage(), $e->getCode());
    } finally {
      // Remove the certificate from disk.
      if (file_exists($tempCertFilename)) {
        unlink($tempCertFilename);
      }
      // Remove the attachment from disk.
      if (file_exists($tempFilename)) {
        unlink($tempFilename);
      }
    }

    if (Response::HTTP_CREATED !== $response->getStatusCode()) {
      throw new FasitResponseException(sprintf('Expected status code 201, received %d', $response->getStatusCode()));
    }

    $content = json_decode($response->getBody()->getContents(), TRUE);
    if (!isset($content['id'])) {
      throw new FasitResponseException('Could not get upload id from response');
    }

    return ['filename' => $originalFilename, 'id' => $content['id']];
  }

  /**
   * Uploads files from file elements to Fasit.
   *
   * @param string $submissionId
   *   The submission id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\os2forms_fasit\Exception\CertificateLocatorException
   * @throws \Drupal\os2forms_fasit\Exception\FasitResponseException
   * @throws \Drupal\os2forms_fasit\Exception\FileTypeException
   *
   * @phpstan-return array<mixed, mixed>
   */
  private function uploadFileElements(string $submissionId): array {
    // Fetch element ids that may contain pdf files.
    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);
    $fileIds = $this->getFileElementKeysFromSubmission($submission);
    $fileStorage = $this->entityTypeManager->getStorage('file');

    $uploads = [];

    foreach ($fileIds as $fileId) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $fileStorage->load($fileId);

      // Ensure it is a pdf.
      if ('application/pdf' !== $file->getMimeType()) {
        throw new FileTypeException('Invalid file type uploaded. Only allowed file type is: pdf');
      }

      $filename = $file->getFilename();

      $fileContent = file_get_contents($file->getFileUri());
      $tempFilename = tempnam(sys_get_temp_dir(), 'attachment');
      file_put_contents($tempFilename, $fileContent);

      $uploads[] = $this->uploadFile($filename, $tempFilename);
    }

    return $uploads;
  }

  /**
   * Returns array of file elements keys in submission.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   The submission id.
   *
   * @phpstan-return array<string, mixed>
   */
  private function getFileElementKeysFromSubmission(WebformSubmission $submission): array {
    $elements = $submission->getWebform()->getElementsDecodedAndFlattened();

    $fileElements = [];

    foreach (self::FILE_ELEMENT_TYPES as $fileElementType) {
      $fileElements[] = $this->getAvailableElementsByType($fileElementType, $elements);
    }

    // https://dev.to/klnjmm/never-use-arraymerge-in-a-for-loop-in-php-5go1
    $fileElements = array_merge(...$fileElements);

    $elementKeys = array_keys($fileElements);

    $fileIds = [];

    foreach ($elementKeys as $elementKey) {
      if (empty($submission->getData()[$elementKey])) {
        continue;
      }

      // Convert occurrences of singular file into array.
      $elementFileIds = (array) $submission->getData()[$elementKey];

      $fileIds[] = $elementFileIds;
    }

    return array_merge(...$fileIds);
  }

  /**
   * Get available elements by type.
   *
   * @param string $type
   *   The element type.
   * @param array $elements
   *   The elements.
   *
   * @phpstan-param array<string, mixed> $elements
   * @phpstan-return array<string, mixed>
   */
  private function getAvailableElementsByType(string $type, array $elements): array {
    $attachmentElements = array_filter($elements, function ($element) use ($type) {
      return $type === $element['#type'];
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

  /**
   * Gets WebformSubmission from id.
   *
   * @param string $submissionId
   *   The submission id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubmission(string $submissionId): EntityInterface {
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    return $storage->load($submissionId);
  }

}
