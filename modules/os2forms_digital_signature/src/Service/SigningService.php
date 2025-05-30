<?php

namespace Drupal\os2forms_digital_signature\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_digital_signature\Form\SettingsForm;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Digital signing service.
 */
class SigningService {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private readonly ImmutableConfig $config;

  /**
   * Webform storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $webformStorage;

  /**
   * WebformSubmission storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $webformSubmissionStorage;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly TimeInterface $time,
    ConfigFactoryInterface $configFactory,
    EntityTypeManager $entityTypeManager,
    private readonly LoggerChannelInterface $logger,
  ) {
    $this->config = $configFactory->get(SettingsForm::$configName);
    $this->webformStorage = $entityTypeManager->getStorage('webform');
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Fetch a new cid.
   *
   * @return string|null
   *   The correlation id.
   */
  public function getCid() : ?string {
    $url = $this->config->get('os2forms_digital_signature_remove_service_url') . 'action=getcid';
    $response = $this->httpClient->request('GET', $url);
    $result = $response->getBody()->getContents();

    $reply = json_decode($result, JSON_OBJECT_AS_ARRAY);

    return $reply['cid'] ?? NULL;
  }

  /**
   * Sign the document.
   *
   * Signing is done by redirecting the user's browser to a url on the signing
   * server that takes the user through the signing flow.
   *
   * This function will never return.
   *
   * @param string $document_uri
   *   A uri to a file on the local server that we want to sign or the file name
   *   on the signing server in the SIGN_PDF_UPLOAD_DIR.
   *   In case of a local file, it must be prefixed by 'http://' or 'https://'
   *   and be readable from the signing server.
   * @param string $cid
   *   The cid made available by the getCid() function.
   * @param string $forward_url
   *   The url on the local server to forward user to afterwards.
   */
  public function sign(string $document_uri, string $cid, string $forward_url):void {
    if (empty($document_uri) || empty($cid) || empty($forward_url)) {
      $this->logger->error('Cannot initiate signing process, check params: document_uri: %document_uri, cid: %cid, forward_url: %forward_url',
        [
          '%document_uri' => $document_uri,
          '%cid' => $cid,
          '%forward_url' => $forward_url,
        ]
      );
      return;
    }

    $hash = $this->getHash($forward_url);
    $params = [
      'action' => 'sign',
      'cid' => $cid,
      'hash' => $hash,
      'uri' => base64_encode($document_uri),
      'forward_url' => base64_encode($forward_url),
    ];
    $url = $this->config->get('os2forms_digital_signature_remove_service_url') . http_build_query($params);

    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * Download the pdf file and return it as a binary string.
   *
   * @param string $filename
   *   The filename as given by the signing server.
   * @param bool $leave
   *   If TRUE, leave the file on the remote server, default is to remove the
   *   file after download.
   * @param bool $annotate
   *   If TRUE, download a pdf with an annotation page.
   * @param array $attributes
   *   An array of pairs of prompts and values that will be added to the
   *   annotation box, e.g.
   *   [
   *     'IP' => $_SERVER['REMOTE_ADDR'],
   *     'Region' => 'Capital Region Copenhagen'
   *   ].
   *
   * @return mixed|bool
   *   The binary data of the pdf or FALSE if an error occurred.
   */
  public function download(string $filename, $leave = FALSE, $annotate = FALSE, $attributes = []) {
    if (empty($filename)) {
      return FALSE;
    }
    if (!preg_match('/^[a-f0-9]{32}\.pdf$/', $filename)) {
      return FALSE;
    }
    $params = [
      'action' => 'download',
      'file' => $filename,
      'leave' => $leave,
      'annotate' => $annotate,
      'attributes' => $attributes,
    ];
    $url = $this->config->get('os2forms_digital_signature_remove_service_url') . http_build_query($params);

    $response = $this->httpClient->request('GET', $url);
    $return = $response->getBody()->getContents();

    if (empty($return)) {
      return FALSE;
    }
    elseif (substr($return, 0, 5) !== '%PDF-') {
      return FALSE;
    }

    return $return;
  }

  /**
   * Calculate the hash value.
   *
   * @param string $value
   *   The value to hash including salt.
   *
   * @return string
   *   The hash value (sha1).
   */
  private function getHash(string $value) : string {
    $hashSalt = $this->config->get('os2forms_digital_signature_sign_hash_salt');
    return sha1($hashSalt . $value);
  }

  /**
   * Deletes stalled webform submissions that were left unsigned.
   *
   * Only checked the webforms that have digital_signature handler enabled and
   * the submission is older that a specified period.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteStalledSubmissions() : void {
    $digitalSignatureWebforms = [];

    // Finding webforms that have any handler.
    $query = $this->webformStorage->getQuery()
      ->exists('handlers');
    $handler_webform_ids = $query->execute();

    // No webforms with handlers, aborting.
    if (empty($handler_webform_ids)) {
      return;
    }

    // Find all with os2forms_digital_signature handlers enabled.
    foreach ($handler_webform_ids as $webform_id) {
      $webform = $this->webformStorage->load($webform_id);
      if (!$webform) {
        continue;
      }

      $handlers = $webform->getHandlers();
      foreach ($handlers as $handler) {
        // Check if the handler is of type 'os2forms_digital_signature'.
        if ($handler->getPluginId() === 'os2forms_digital_signature' && $handler->isEnabled()) {
          $digitalSignatureWebforms[] = $webform->id();
          break;
        }
      }
    }

    // No webforms, aborting.
    if (empty($digitalSignatureWebforms)) {
      return;
    }

    // Find all stalled webform submissions of digital signature forms.
    $retention_period = ($this->config->get('os2forms_digital_signature_submission_retention_period')) ?? 300;
    $timestamp_threshold = $this->time->getRequestTime() - $retention_period;
    $query = $this->webformSubmissionStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('webform_id', $digitalSignatureWebforms, 'IN')
      ->condition('locked', 0)
      ->condition('created', $timestamp_threshold, '<');
    $submission_ids = $query->execute();

    // No submissions, aborting.
    if (empty($submission_ids)) {
      return;
    }

    // Deleting all stalled webform submissions.
    foreach ($submission_ids as $submission_id) {
      $submission = $this->webformSubmissionStorage->load($submission_id);
      $submission->delete();
    }
  }

}
