<?php

namespace Drupal\os2forms_digital_signature\Plugin\WebformHandler;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\os2forms_digital_signature\Service\SigningService;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformSubmissionInterface;
use phpseclib3\Crypt\Hash;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Webform submission debug handler.
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
  protected $moduleHandler;

  /**
   * The webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
//    $instance->renderer = $container->get('renderer');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    return $instance;
  }

//  /**
//   * {@inheritdoc}
//   */
//  public function defaultConfiguration() {
//    return [
//      'format' => 'yaml',
//      'submission' => FALSE,
//    ];
//  }

//  /**
//   * {@inheritdoc}
//   */
//  public function getSummary() {
//    $settings = $this->getSettings();
//    switch ($settings['format']) {
//      case static::FORMAT_JSON:
//        $settings['format'] = $this->t('JSON');
//        break;
//
//      case static::FORMAT_YAML:
//      default:
//        $settings['format'] = $this->t('YAML');
//        break;
//    }
//    return [
//      '#settings' => $settings,
//    ] + parent::getSummary();
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//    $form['debug_settings'] = [
//      '#type' => 'fieldset',
//      '#title' => $this->t('Debug settings'),
//    ];
//    $form['debug_settings']['format'] = [
//      '#type' => 'select',
//      '#title' => $this->t('Data format'),
//      '#options' => [
//        static::FORMAT_YAML => $this->t('YAML'),
//        static::FORMAT_JSON => $this->t('JSON'),
//      ],
//      '#default_value' => $this->configuration['format'],
//    ];
//    $form['debug_settings']['submission'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Include submission properties'),
//      '#description' => $this->t('If checked, all submission properties and values  will be included in the displayed debug information. This includes sid, created, updated, completed, and more.'),
//      '#return_value' => TRUE,
//      '#default_value' => $this->configuration['submission'],
//    ];
//    return $this->setSettingsParents($form);
//  }

//  /**
//   * {@inheritdoc}
//   */
//  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
//    parent::submitConfigurationForm($form, $form_state);
//    $this->applyFormStateToConfiguration($form_state);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
//    $settings = $this->getSettings();
//
//    $data = ($settings['submission'])
//      ? $webform_submission->toArray(TRUE)
//      : $webform_submission->getData();
//    WebformElementHelper::convertRenderMarkupToStrings($data);
//
//    $label = ($settings['submission'])
//      ? $this->t('Submitted properties and values are:')
//      : $this->t('Submitted values are:');
//
//    $build = [
//      'label' => ['#markup' => $label],
//      'data' => [
//        '#markup' => ($settings['format'] === static::FORMAT_JSON)
//          ? json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT)
//          : WebformYaml::encode($data),
//        '#prefix' => '<pre>',
//        '#suffix' => '</pre>',
//      ],
//    ];
//    $message = $this->renderer->renderPlain($build);
//
//    $this->messenger()->addWarning($message);
//  }

  /**
    * {@inheritdoc}
    */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    if ($webform_submission->isLocked()) {
      return;
    }

    $attachments = $this->getSubmissionAttachment($webform_submission);
    //$destination = 'private://webform/signing' . $webform_submission->uuid() .'.pdf';
    //$pdfToSign = file_put_contents($destination, $attachment['filecontent'], FILE_APPEND);

    // TODO: think about file URL protection.
    $destinationDir = 'public://signing';
    \Drupal::service('file_system')->prepareDirectory($destinationDir, FileSystemInterface::CREATE_DIRECTORY);

    $destination = $destinationDir . '/' . $webform_submission->uuid() .'.pdf';

    // Save the file data.
    /** @var FileInterface $fileSubmissionPdf */
    $fileSubmissionPdf = \Drupal::service('file.repository')->writeData($attachments[0]['filecontent'], $destination, FileSystemInterface::EXISTS_REPLACE);

    if ($fileSubmissionPdf) {
      // Set the status to permanent to prevent file deletion on cron.
      //$fileSubmissionPdf->setPermanent();

      $fileSubmissionPdf->save();
      $submissionPdfPublicUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($fileSubmissionPdf->getFileUri());
    }

    if ($submissionPdfPublicUrl) {
      // For testing.
      //$submissionPdfPublicUrl = 'https://signering.bellcom.dk/test/test-form.pdf';

      /** @var SigningService $signingService */
      $signingService = \Drupal::service('os2forms_digital_signature.signing_service');

      $cid = $signingService->get_cid();
      if (empty($cid)) {
        die('Failed to obtain cid. Is server running?');
      }

      // Creating hash.
      $salt = \Drupal::service('settings')->get('hash_salt');
      $hash = Crypt::hashBase64($webform_submission->uuid() . $webform_submission->getWebform()->id() . $salt);

      $signatureCallbackUrl = Url::fromRoute('os2forms_digital_signature.sign_callback', ['uuid' => $webform_submission->uuid(), 'hash' => $hash]);

      // Starting signing
      $signingService->sign($submissionPdfPublicUrl, $cid, $signatureCallbackUrl->setAbsolute()->toString());
    }
  }


  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    return;

    if ($webform_submission->isLocked()) {
      return;
    }
//     Getting attachments.
    $attachments = $this->getMessageAttachments($webform_submission);
    dpm($attachments);
    return;
//
//    // Getting attachment as file. TODO: is there a better way to do it?
//    $data = $attachments[0]['filecontent'];
//    $destination = 'sites/default/files/teststan' . $webform_submission->id() .'.pdf';
    $submissionPdfPublicUrl = 'https://signering.bellcom.dk/test/test-form.pdf';


//    // Write data to the file.
//    $result = file_put_contents($destination, $data, FILE_APPEND);
////    $response = \Drupal::httpClient()->get($url, ['sink' => $destination]);
//
    /** @var SigningService $signingService */
    $signingService = \Drupal::service('os2forms_digital_signature.signing_service');

    $cid = $signingService->get_cid();
    if(empty($cid)) {
      die('Failed to obtain cid. Is server running?');
    }

    $signatureCallbackUrl = Url::fromRoute('os2forms_digital_signature.test', ['webform_submission' => $webform_submission->id()]);

    // Starting signing
    $signingService->sign($submissionPdfPublicUrl, $cid, $signatureCallbackUrl->setAbsolute()->toString());

    // Making redirect.
//    $response = new RedirectResponse('https://google.com');
//    $response->send();

//    $response = new RedirectResponse($url->setAbsolute()->toString());
//    $response->send();

//    $webform_submission->resave();
  }

  /**
   * Get OS2forms file attachment.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *    A webform submission.
   *
   * @return array|null
   *    Array of attachment data.
   */
  protected function getSubmissionAttachment(WebformSubmissionInterface $webform_submission) {
    $attachment = NULL;
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    $element_attachments = $this->getWebform()->getElementsAttachments();
    foreach ($element_attachments as $element_attachment) {
      // Check if the element attachment key is excluded and should not attach any files.
      if (isset($this->configuration['excluded_elements'][$element_attachment])) {
        continue;
      }

      $element = $elements[$element_attachment];
      if ($element['#type'] == 'os2forms_attachment') {
        /** @var \Drupal\webform\Plugin\WebformElementAttachmentInterface $element_plugin */
        $element_plugin = $this->elementManager->getElementInstance($element);
        $attachment = $element_plugin->getEmailAttachments($element, $webform_submission);
      }
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
