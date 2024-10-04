<?php

namespace Drupal\os2forms_digital_signature\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\os2forms_digital_signature\Service\SigningService;
use Drupal\webform\Entity\WebformSubmission;
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
   * @return RedirectResponse
   *   Redirect response to form submission confirmation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function signCallback($uuid, $hash) {
    // Load the webform submission entity by UUID.
    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['uuid' => $uuid]);

    // Since loadByProperties returns an array, we need to fetch the first item.
    $webformSubmission = $submissions ? reset($submissions) : NULL;
    if (!$webformSubmission) {
      throw new NotFoundHttpException();
    }

    $webformId = $webformSubmission->getWebform()->id();

    // Checking hash.
    $salt = \Drupal::service('settings')->get('hash_salt');
    $tmpHash = Crypt::hashBase64($uuid . $webformId . $salt);
    if ($hash !== $tmpHash) {
      throw new NotFoundHttpException();
    }

    /** @var SigningService $signingService */
    $signingService = \Drupal::service('os2forms_digital_signature.signing_service');

    $signeFilename = \Drupal::request()->get('file');
    $signedFileContent = $signingService->download($signeFilename);
    if (!$signedFileContent) {
      throw new NotFoundHttpException();
    }

    // Prepare the directory to ensure it exists and is writable.
    $file_system = \Drupal::service('file_system');
    $expectedFileUri = "private://webform/$webformId/digital_signature/$uuid.pdf";
    $directory = dirname($expectedFileUri);

    if (!$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
      // TODO: throw error.
      //\Drupal::logger('my_module')->error('Failed to prepare directory %directory.', ['%directory' => $directory]);
    }

    // Write the data to the file using Drupal's file system service.
    try {
      $file_system->saveData($signedFileContent, $expectedFileUri , FileSystemInterface::EXISTS_REPLACE);
    }
    catch (\Exception $e) {
      // TODO: throw error.
      //\Drupal::logger('my_module')->error('Failed to write to file %uri: @message', ['%uri' => $uri, '@message' => $e->getMessage()]);
    }

    // Updating webform submission.
    $webformSubmission->setLocked(TRUE);
    $webformSubmission->save();

    // Build the URL for the webform submission confirmation page.
    $confirmation_url = Url::fromRoute('entity.webform.confirmation', [
      'webform' => $webformId,
      'webform_submission' => $webformSubmission->id(),
    ])->toString();

    // Redirect to the webform confirmation page.
    $response = new RedirectResponse($confirmation_url);
    return $response;
  }




  public function test(WebformSubmission $webform_submission) {
    $webformId = $webform_submission->getWebform()->id();
    $sid = $webform_submission->id();

    $fileUri = "private://webform/$webformId/digital_signature/$sid.pdf";
//    $webform_submission->resave();
//    dpm('Done');

    /** @var SigningService $signingService */
    $signingService = \Drupal::service('os2forms_digital_signature.signing_service');

    $signeFilename = \Drupal::request()->get('file');
    $signedFileContent = $signingService->download($signeFilename);

    // Get the FileSystem service.
    $file_system = \Drupal::service('file_system');

    // Prepare the directory to ensure it exists and is writable.
    $directory = dirname($fileUri);
    if (!$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
      //\Drupal::logger('my_module')->error('Failed to prepare directory %directory.', ['%directory' => $directory]);
    }

    // Write the data to the file using Drupal's file system service.
    try {
      $result = $file_system->saveData($signedFileContent, $fileUri , FileSystemInterface::EXISTS_REPLACE);
    }
    catch (\Exception $e) {
      //\Drupal::logger('my_module')->error('Failed to write to file %uri: @message', ['%uri' => $uri, '@message' => $e->getMessage()]);
    }

    $webform_submission->setLocked(TRUE);
    $webform_submission->save();

    // Build the URL for the webform confirmation page.
    $confirmation_url = Url::fromRoute('entity.webform.confirmation', [
      'webform' => $webformId,
      'webform_submission' => $sid,
    ])->toString();

    // Redirect to the webform confirmation page.
    $response = new RedirectResponse($confirmation_url);
    return $response;
  }
}
