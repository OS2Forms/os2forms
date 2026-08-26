<?php

namespace Drupal\os2forms_digital_signature\Plugin\WebformHandler;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileRepositoryInterface;
use Drupal\os2forms_digital_signature\Service\SigningDocumentResolver;
use Drupal\os2forms_digital_signature\Service\SigningService;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Digital signature webform handler.
 *
 * @WebformHandler(
 *   id = "os2forms_digital_signature",
 *   label = @Translation("Digital Signature"),
 *   category = @Translation("OS2Forms"),
 *   description = @Translation("Sends file to digital signature."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DigitalSignatureWebformHandler extends WebformHandlerBase {

  /**
   * Logger for channel - os2forms_digital_signature.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected readonly LoggerInterface $logger;

  /**
   * File system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected readonly FileSystemInterface $fileSystem;

  /**
   * File repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected readonly FileRepositoryInterface $fileRepository;

  /**
   * File URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected readonly FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Signing document resolver.
   *
   * @var \Drupal\os2forms_digital_signature\Service\SigningDocumentResolver
   */
  protected readonly SigningDocumentResolver $signingDocumentResolver;

  /**
   * OS2Forms signing service.
   *
   * @var \Drupal\os2forms_digital_signature\Service\SigningService
   */
  protected readonly SigningService $signingService;

  /**
   * Settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected readonly Settings $settings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.channel.os2forms_digital_signature');
    $instance->fileSystem = $container->get('file_system');
    $instance->fileRepository = $container->get('file.repository');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->signingDocumentResolver = $container->get('os2forms_digital_signature.signing_document_resolver');
    $instance->signingService = $container->get('os2forms_digital_signature.signing_service');
    $instance->settings = $container->get('settings');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();

    if ($webform_submission->isLocked()) {
      return;
    }

    $attachment = $this->signingDocumentResolver->resolve($webform_submission);
    if (!$attachment) {
      $this->logger->error('Attachment cannot be created webform: %webform, webform_submission: %webform_submission',
        [
          '%webform' => $webform->id(),
          '%webform_submission' => $webform_submission->uuid(),
        ]
      );
      return;
    }

    $destinationDir = 'private://signing';
    if (!$this->fileSystem->prepareDirectory($destinationDir, FileSystemInterface::CREATE_DIRECTORY)) {
      $this->logger->error('File directory cannot be created: %filedirectory', ['%filedirectory' => $destinationDir]);
      return;
    }

    $fileUri = $destinationDir . '/' . $webform_submission->uuid() . '.pdf';

    // Save the file data.
    try {
      $fileToSign = $this->fileRepository->writeData($attachment['filecontent'], $fileUri, FileExists::Replace);
    }
    catch (\Exception $e) {
      $this->logger->error('File cannot be saved: %fileUri, error: %error',
        [
          '%fileUri' => $fileUri,
          '%error' => $e->getMessage(),
        ]);
      return;
    }

    $fileToSign->save();
    $fileToSignPublicUrl = $this->fileUrlGenerator->generateAbsoluteString($fileToSign->getFileUri());

    $cid = $this->signingService->getCid();
    if (empty($cid)) {
      $this->logger->error('Failed to obtain cid. Is server running?');
      return;
    }

    // Creating hash.
    $salt = $this->settings->get('hash_salt');
    $hash = Crypt::hashBase64($webform_submission->uuid() . $webform->id() . $salt);

    $callbackParams = [
      'uuid' => $webform_submission->uuid(),
      'hash' => $hash,
    ];

    $signatureCallbackUrl = Url::fromRoute('os2forms_digital_signature.sign_callback', $callbackParams);

    // Starting signing, if everything is correct - this funcition will start
    // redirect.
    $this->signingService->sign($fileToSignPublicUrl, $cid, $signatureCallbackUrl->setAbsolute()->toString());
  }

}
