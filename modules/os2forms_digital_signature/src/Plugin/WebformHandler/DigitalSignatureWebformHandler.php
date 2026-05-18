<?php

namespace Drupal\os2forms_digital_signature\Plugin\WebformHandler;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileRepositoryInterface;
use Drupal\os2forms_attachment\Os2formsAttachmentPrintBuilder;
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
  public function defaultConfiguration() {
    return [
      'attachment_element' => '',
      'signature_position' => Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['attachment_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment element to sign'),
      '#description' => $this->t('Select the webform element whose generated or uploaded PDF should be signed.'),
      '#options' => $this->getAttachmentElementOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $this->configuration['attachment_element'],
      '#required' => TRUE,
    ];

    $form['signature_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Signature validation text position'),
      '#description' => $this->t('Where the digital signature validation text is placed in the generated PDF. Only applies when the selected element is an <em>OS2Forms Attachment</em>; ignored for uploaded PDF documents.'),
      '#options' => [
        Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_FOOTER => $this->t('Footer (repeats on every page)'),
        Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_HEADER => $this->t('Header (repeats on every page)'),
        Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT => $this->t('After content (end of document)'),
        Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_BEFORE_CONTENT => $this->t('Before content (start of document)'),
      ],
      '#default_value' => $this->configuration['signature_position'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['attachment_element'] = $values['attachment_element'] ?? '';
    $this->configuration['signature_position'] = $values['signature_position']
      ?? Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $elementKey = $this->configuration['attachment_element'] ?? '';
    $position = $this->configuration['signature_position'] ?? Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT;

    return [
      '#markup' => $this->t('Sign attachment: <em>@element</em><br />Signature position: <em>@position</em>', [
        '@element' => $elementKey !== '' ? $elementKey : $this->t('not configured'),
        '@position' => $position,
      ]),
    ];
  }

  /**
   * Build the dropdown options for the attachment element selector.
   *
   * Lists every element implementing WebformElementAttachmentInterface
   * (covers os2forms_attachment and os2forms_digital_signature_document).
   *
   * @return array
   *   Map of element key => human label.
   */
  protected function getAttachmentElementOptions(): array {
    $webform = $this->getWebform();
    if (!$webform) {
      return [];
    }

    $elements = $webform->getElementsInitializedAndFlattened();
    $options = [];
    foreach ($webform->getElementsAttachments() as $key) {
      $element = $elements[$key] ?? NULL;
      if (!$element) {
        continue;
      }
      $title = $element['#title'] ?? $key;
      $type = $element['#type'] ?? '';
      $options[$key] = sprintf('%s (%s) [%s]', $title, $key, $type);
    }
    return $options;
  }

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
   * Resolves the attachment element configured on the handler, asks its
   * plugin for the email attachment payload, and returns the first item.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array|null
   *   Array of attachment data, or NULL when no attachment is available.
   *
   * @throws \Exception
   */
  protected function getSubmissionAttachment(WebformSubmissionInterface $webform_submission) {
    $elementKey = $this->configuration['attachment_element'] ?? '';
    if ($elementKey === '') {
      $this->logger->error('Digital signature handler has no attachment_element configured for webform %webform.', [
        '%webform' => $this->getWebform()->id(),
      ]);
      return NULL;
    }

    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    if (!isset($elements[$elementKey])) {
      $this->logger->error('Configured attachment element %element does not exist on webform %webform.', [
        '%element' => $elementKey,
        '%webform' => $this->getWebform()->id(),
      ]);
      return NULL;
    }

    $element = $elements[$elementKey];
    $element['#digital_signature'] = TRUE;
    $element['#digital_signature_position'] = $this->configuration['signature_position']
      ?? Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT;

    /** @var \Drupal\webform\Plugin\WebformElementAttachmentInterface $element_plugin */
    $element_plugin = $this->elementManager->getElementInstance($element);
    $attachments = $element_plugin->getEmailAttachments($element, $webform_submission);

    if (empty($attachments)) {
      return NULL;
    }

    // If the source is an uploaded managed file, attach the FID so the
    // signed file can replace the upload rather than creating a new file.
    if ($fid = $webform_submission->getElementData($elementKey)) {
      $attachments[0]['fid'] = $fid;
    }

    $attachment = reset($attachments);

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
