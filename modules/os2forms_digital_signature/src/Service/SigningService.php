<?php

namespace Drupal\os2forms_digital_signature\Service;

class SigningService {

  private $reply = [];

  private string $SIGN_REMOTE_SERVICE_URL = 'https://signering.bellcom.dk/sign.php?';

  /**
   * Default constructor.
   */
  public function __construct() {
  }

  /**
   * Fetch a new cid.
   *
   * @return string
   *   The correlation id.
   */
  public function get_cid() : ?string {
    $url = $this->SIGN_REMOTE_SERVICE_URL . 'action=getcid';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);

    $this->reply = json_decode($result, JSON_OBJECT_AS_ARRAY);

    return $this->reply['cid'] ?? NULL;
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
   * @param bool $leave
   *   Leave the pdf file on the remote server.
   *
   * @throws SignParameterException
   *   Empty url or cid given.
   */
  public function sign(string $document_uri, string $cid, string $forward_url, bool $leave = FALSE) {
    if(empty($document_uri) || empty($cid) || empty($forward_url)) {
      //throw new SignParameterException();
    }

    $hash = SigningUtil::get_hash($forward_url);
    $params = ['action' => 'sign', 'cid' => $cid, 'hash' => $hash, 'uri' => base64_encode($document_uri), 'forward_url' => base64_encode($forward_url)];
    $url = $this->SIGN_REMOTE_SERVICE_URL . http_build_query($params);

    SigningUtil::url_forward($url);
  }

  /**
   * Verify the document.
   *
   * Verifying is done by redirecting the user's browser to a url on the signing server that takes the user
   * through the verify flow.
   *
   * This function will never return.
   *
   * @param string $forward_url
   *   A url to a file on the local server that we want to sign or the full file name on the signing server.
   *   In case of a local file, it must be prefixed by 'http://' or 'https://' and be readable from the signing server.
   *
   * @throws SignParameterException
   *   Empty url or cid given.
   *
   * @todo Verifying the pdf is yet to be implemented on the signing server.
   */
  public function verify(string $document_uri, string $cid, string $forward_url) {
    SigningUtil::logger('Verify unimplemented!', 'WARNING');
    if(empty($forward_url)) {
      //throw new SignParameterException();
    }

    $hash = SigningUtil::get_hash($forward_url);
    $params = ['action' => 'verify', 'hash' => $hash, 'uri' => base64_encode($document_uri), 'forward_url' => base64_encode($forward_url)];
    $url = $this->SIGN_REMOTE_SERVICE_URL . http_build_query($params);

    SigningUtil::url_forward($url);
  }

  /**
   * Download the pdf file and return it as a binary string.
   *
   * @param string $filename
   *   The filename as given by the signing server.
   * @param boolean $leave
   *   If TRUE, leave the file on the remote server, default is to remove the file after download.
   *
   * @return mixed
   *   The binary data of the pdf or an array if an error occured.
   */
  public function download(string $filename, $leave = FALSE) {
    if (empty($filename)) {
      return FALSE;
      //throw new SignParameterException('Filename cannot be empty');
    }
    if (!preg_match('/^[a-f0-9]{32}\.pdf$/', $filename)) {
      return FALSE;
      //throw new SignParameterException('Incorrect filename given');
    }
    $params = ['action' => 'download', 'file' => $filename, 'leave' => $leave];
    $url = $this->SIGN_REMOTE_SERVICE_URL . http_build_query($params);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $return = curl_exec($curl);

    if (empty($return)) {
      return FALSE;
      //$return = ['error' => TRUE, 'message' => 'Empty file'];
    }
    elseif (substr($return, 0, 5) !== '%PDF-') {
      return FALSE;
      //$return = ['error' => TRUE, 'message' => 'Not a PDF file'];
    }

    return $return;
  }

  /**
   * Download the pdf file and send it to the user's browser.
   *
   * @param string $filename
   *   The filename.
   *
   * @throws SignException
   */
  public function view(string $filename) {
    $pdf = $this->download($filename);
    if(is_array($pdf)) {
      print 'Unable to view file: ' . $pdf['message'];
      return;
    }

    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));

    print $pdf;
  }
}
