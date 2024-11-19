<?php

namespace Drupal\os2forms_digital_signature\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\os2forms_digital_signature\Form\SettingsForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SigningService {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private readonly ImmutableConfig $config;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get(SettingsForm::$configName);
  }

  /**
   * Fetch a new cid.
   *
   * @return string|NULL
   *   The correlation id.
   */
  public function get_cid() : ?string {
    $url = $this->config->get('os2forms_digital_signature_remove_service_url') . 'action=getcid';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);

    $reply = json_decode($result, JSON_OBJECT_AS_ARRAY);

    return $reply['cid'] ?? NULL;
  }

  /**
   * Sign the document.
   *
   * Signing is done by redirecting the user's browser to a url on the signing server that takes the user
   * through the signing flow.
   *
   * This function will never return.
   *
   * @param string $document_uri
   *   A uri to a file on the local server that we want to sign or the file name on the signing server in the SIGN_PDF_UPLOAD_DIR.
   *   In case of a local file, it must be prefixed by 'http://' or 'https://' and be readable from the signing server.
   * @param string $cid
   *   The cid made available by the get_cid() function.
   * @param string $forward_url
   *   The url on the local server to forward user to afterwards.
   *
   * @return void
   */
  public function sign(string $document_uri, string $cid, string $forward_url):void {
    if (empty($document_uri) || empty($cid) || empty($forward_url)) {
      \Drupal::logger('os2forms_digital_signature')->error('Cannot initiate signing process, check params: document_uri: %document_uri, cid: %cid, forward_url: %forward_url', ['%document_uri' => $document_uri, '%cid' => $cid, '%forward_url' => $forward_url]);
      return;
    }

    $hash = $this->getHash($forward_url);
    $params = ['action' => 'sign', 'cid' => $cid, 'hash' => $hash, 'uri' => base64_encode($document_uri), 'forward_url' => base64_encode($forward_url)];
    $url = $this->config->get('os2forms_digital_signature_remove_service_url') . http_build_query($params);

    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * Download the pdf file and return it as a binary string.
   *
   * @param string $filename
   *   The filename as given by the signing server.
   * @param boolean $leave
   *   If TRUE, leave the file on the remote server, default is to remove the file after download.
   * @param boolean $annotate
   *    If TRUE, download a pdf with an annotation page.
   * @param array $attributes
   *    An array of pairs of prompts and values that will be added to the annotation box, e.g.,
   *      ['IP' => $_SERVER['REMOTE_ADDR'], 'Region' => 'Capital Region Copenhagen'].
   *
   * @return mixed|bool
   *   The binary data of the pdf or FALSE if an error occurred.
   */
  public function download(string $filename, $leave = FALSE, $annotate = TRUE, $attributes = []) {
    if (empty($filename)) {
      return FALSE;
    }
    if (!preg_match('/^[a-f0-9]{32}\.pdf$/', $filename)) {
      return FALSE;
    }
    $params = ['action' => 'download', 'file' => $filename, 'leave' => $leave, 'annotate' => $annotate, 'attributes' => $attributes];
    $url = $this->config->get('os2forms_digital_signature_remove_service_url') . http_build_query($params);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $return = curl_exec($curl);

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
   * @param string $name
   *   The value to hash including salt.
   *
   * @return string
   *   The hash value (sha1).
   */
  private function getHash(string $value) : string {
    $hashSalt = $this->config->get('os2forms_digital_signature_sign_hash_salt');
    return sha1($hashSalt . $value);
  }

}
