<?php

namespace Drupal\os2forms_digital_signature\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\os2forms_digital_signature\Service\SigningService;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DigitalSignatureController {

  /**
   * Callback for the file being signed.
   *
   * Expecting the file name to be coming as GET parameter.
   *
   * @param $uuid
   *   Webform submission UUID.
   * @param $hash
   *    Hash to check if the request is authentic.
   * @param $fid
   *    File to replace (optional).
   *
   * @return RedirectResponse
   *   Redirect response to form submission confirmation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function signCallback($uuid, $hash, $fid = NULL) {
    // Load the webform submission entity by UUID.
    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['uuid' => $uuid]);

    // Since loadByProperties returns an array, we need to fetch the first item.
    /** @var WebformSubmissionInterface $webformSubmission */
    $webformSubmission = $submissions ? reset($submissions) : NULL;
    if (!$webformSubmission) {
      // Submission does not exist.
      throw new NotFoundHttpException();
    }

    $webformId = $webformSubmission->getWebform()->id();

    // Checking the action
    $action = \Drupal::request()->query->get('name');
    if ($action == 'cancel') {
      $cancelUrl = $webformSubmission->getWebform()->toUrl()->toString();

      // Redirect to the webform confirmation page.
      $response = new RedirectResponse($cancelUrl);
      return $response;
    }

    // Checking hash.
    $salt = \Drupal::service('settings')->get('hash_salt');
    $tmpHash = Crypt::hashBase64($uuid . $webformId . $salt);
    if ($hash !== $tmpHash) {
      // Submission exist, but the provided hash is incorrect.
      throw new NotFoundHttpException();
    }

    /** @var SigningService $signingService */
    $signingService = \Drupal::service('os2forms_digital_signature.signing_service');

    $signedFilename = \Drupal::request()->get('file');
    $signedFileContent = $signingService->download($signedFilename);
    if (!$signedFileContent) {
      \Drupal::logger('os2forms_digital_signature')->warning('Missing file on remote server %file.', ['%file' => $signedFilename]);
      throw new NotFoundHttpException();
    }

    /** @var FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    // If $fid is present - we are replacing uploaded/managed file, otherwise creating a new one.
    if ($fid) {
      $file = File::load($fid);
      $expectedFileUri = $file->getFileUri();
    } else {
      // Prepare the directory to ensure it exists and is writable.
      $expectedFileUri = "private://webform/$webformId/digital_signature/$uuid.pdf";
      $directory = dirname($expectedFileUri);

      if (!$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
        \Drupal::logger('os2forms_digital_signature')->error('Failed to prepare directory %directory.', ['%directory' => $directory]);
      }
    }

    // Write the data to the file using Drupal's file system service.
    try {
      $file_system->saveData($signedFileContent, $expectedFileUri , FileSystemInterface::EXISTS_REPLACE);

      // Updating webform submission.
      $webformSubmission->setLocked(TRUE);
      $webformSubmission->save();

      // If file existing, resave the file to update the size and etc.
      if ($fid) {
        File::load($fid)->save();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('os2forms_digital_signature')->error('Failed to write to file %uri: @message', ['%uri' => $expectedFileUri, '@message' => $e->getMessage()]);
    }

    // Build the URL for the webform submission confirmation page.
    $confirmation_url = Url::fromRoute('entity.webform.confirmation', [
      'webform' => $webformId,
      'webform_submission' => $webformSubmission->id(),
    ])->toString();

    // Redirect to the webform confirmation page.
    $response = new RedirectResponse($confirmation_url);
    return $response;
  }

}
