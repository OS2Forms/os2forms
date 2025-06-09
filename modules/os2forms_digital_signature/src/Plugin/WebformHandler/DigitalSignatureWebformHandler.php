<?php

namespace Drupal\os2forms_digital_signature\Plugin\WebformHandler;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileRepositoryInterface;
use Drupal\os2forms_digital_signature\Service\SigningService;
use Drupal\webform\Plugin\WebformElementManagerInterface;
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private readonly ModuleHandlerInterface $moduleHandler;

  /**
   * The webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  private readonly WebformElementManagerInterface $elementManager;

  /**
   * Logger for channel - os2forms_digital_signature.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * File system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private readonly FileSystemInterface $fileSystem;

  /**
   * File repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  private readonly FileRepositoryInterface $fileRepository;

  /**
   * File URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private readonly FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * OS2Forms signing service.
   *
   * @var \Drupal\os2forms_digital_signature\Service\SigningService
   */
  private readonly SigningService $signingService;

  /**
   * Settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  private readonly Settings $settings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    $instance->logger = $container->get('logger.channel.os2forms_digital_signature');
    $instance->fileSystem = $container->get('file_system');
    $instance->fileRepository = $container->get('file.repository');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
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

    $attachment = $this->getSubmissionAttachment($webform_submission);
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

    $attachmentFid = $attachment['fid'] ?? NULL;
    $signatureCallbackUrl = Url::fromRoute('os2forms_digital_signature.sign_callback',
      [
        'uuid' => $webform_submission->uuid(),
        'hash' => $hash,
        'fid' => $attachmentFid,
      ]
    );

    // Starting signing, if everything is correct - this funcition will start
    // redirect.
    $this->signingService->sign($fileToSignPublicUrl, $cid, $signatureCallbackUrl->setAbsolute()->toString());
  }

  /**
   * Get OS2forms file attachment.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array|null
   *   Array of attachment data.
   *
   * @throws \Exception
   */
  protected function getSubmissionAttachment(WebformSubmissionInterface $webform_submission) {
    $attachments = NULL;
    $attachment = NULL;

    // Getting all element types that are added to the webform.
    //
    // Priority is the following: check for os2forms_digital_signature_document,
    // is not found try serving os2forms_attachment.
    $elementTypes = array_column($this->getWebform()->getElementsDecodedAndFlattened(), '#type');
    $attachmentType = '';
    if (in_array('os2forms_digital_signature_document', $elementTypes)) {
      $attachmentType = 'os2forms_digital_signature_document';
    }
    elseif (in_array('os2forms_attachment', $elementTypes)) {
      $attachmentType = 'os2forms_attachment';
    }

    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    $element_attachments = $this->getWebform()->getElementsAttachments();
    foreach ($element_attachments as $element_attachment) {
      // Check if the element attachment key is excluded and should not attach
      // any files.
      if (isset($this->configuration['excluded_elements'][$element_attachment])) {
        continue;
      }

      $element = $elements[$element_attachment];

      if ($element['#type'] == $attachmentType) {
        /** @var \Drupal\webform\Plugin\WebformElementAttachmentInterface $element_plugin */
        $element_plugin = $this->elementManager->getElementInstance($element);
        $attachments = $element_plugin->getEmailAttachments($element, $webform_submission);

        // If we are dealing with an uploaded file, attach the FID.
        if ($fid = $webform_submission->getElementData($element_attachment)) {
          $attachments[0]['fid'] = $fid;
        }
        break;
      }
    }

    if (!empty($attachments)) {
      $attachment = reset($attachments);
    }

    // For SwiftMailer && Mime Mail use filecontent and not the filepath.
    // @see \Drupal\swiftmailer\Plugin\Mail\SwiftMailer::attachAsMimeMail
    // @see \Drupal\mimemail\Utility\MimeMailFormatHelper::mimeMailFile
    // @see https://www.drupal.org/project/webform/issues/3232756
    if ($this->moduleHandler->moduleExists('swiftmailer')
      || $this->moduleHandler->moduleExists('mimemail')) {
      if (isset($attachment['filecontent']) && isset($attachment['filepath'])) {
        unset($attachment['filepath']);
      }
    }

    return $attachment;
  }

}
