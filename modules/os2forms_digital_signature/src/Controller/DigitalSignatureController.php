<?php

namespace Drupal\os2forms_digital_signature\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\os2forms_digital_signature\Service\SigningService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Digital Signature Controller.
 */
class DigitalSignatureController extends ControllerBase {

  /**
   * File Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $fileStorage;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly LoggerInterface $logger,
    private readonly Settings $settings,
    private readonly SigningService $signingService,
    private readonly FileSystemInterface $fileSystem,
    private readonly RequestStack $requestStack,
  ) {
    $this->fileStorage = $this->entityTypeManager()->getStorage('file');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.os2forms_digital_signature'),
      $container->get('settings'),
      $container->get('os2forms_digital_signature.signing_service'),
      $container->get('file_system'),
      $container->get('request_stack'),
    );
  }

  /**
   * Callback for the file being signed.
   *
   * Expecting the file name to be coming as GET parameter.
   *
   * @param string $uuid
   *   Webform submission UUID.
   * @param string $hash
   *   Hash to check if the request is authentic.
   * @param int|null $fid
   *   File to replace (optional).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to form submission confirmation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function signCallback($uuid, $hash, $fid = NULL) {
    // Load the webform submission entity by UUID.
    $submissions = $this->entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['uuid' => $uuid]);

    // Since loadByProperties returns an array, we need to fetch the first item.
    /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
    $webformSubmission = $submissions ? reset($submissions) : NULL;
    if (!$webformSubmission) {
      // Submission does not exist.
      throw new NotFoundHttpException();
    }

    $webformId = $webformSubmission->getWebform()->id();

    // Checking the action.
    $request = $this->requestStack->getCurrentRequest();

    $action = $request->query->get('action');
    if ($action == 'cancel') {
      $cancelUrl = $webformSubmission->getWebform()->toUrl()->toString();

      // Redirect to the webform confirmation page.
      $response = new RedirectResponse($cancelUrl);
      return $response;
    }

    // Checking hash.
    $salt = $this->settings->get('hash_salt');
    $tmpHash = Crypt::hashBase64($uuid . $webformId . $salt);
    if ($hash !== $tmpHash) {
      // Submission exist, but the provided hash is incorrect.
      throw new NotFoundHttpException();
    }

    $signedFilename = $request->get('file');
    $signedFileContent = $this->signingService->download($signedFilename);
    if (!$signedFileContent) {
      $this->logger->warning('Missing file on remote server %file.', ['%file' => $signedFilename]);
      throw new NotFoundHttpException();
    }

    // If $fid is present - we are replacing uploaded/managed file, otherwise
    // creating a new one.
    if ($fid) {
      $file = $this->fileStorage->load($fid);
      $expectedFileUri = $file->getFileUri();
    }
    else {
      // Prepare the directory to ensure it exists and is writable.
      $expectedFileUri = "private://webform/$webformId/digital_signature/$uuid.pdf";
      $directory = dirname($expectedFileUri);

      if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
        $this->logger->error('Failed to prepare directory %directory.', ['%directory' => $directory]);
      }
    }

    // Write the data to the file using Drupal's file system service.
    try {
      $this->fileSystem->saveData($signedFileContent, $expectedFileUri, FileExists::Replace);

      // Updating webform submission.
      $webformSubmission->setLocked(TRUE);
      $webformSubmission->save();

      // If file existing, resave the file to update the size and etc.
      if ($fid) {
        $this->fileStorage->load($fid)?->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to write to file %uri: @message',
        [
          '%uri' => $expectedFileUri,
          '@message' => $e->getMessage(),
        ]);
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
