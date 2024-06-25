<?php

namespace Drupal\os2forms_encrypt\Helper;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Os2FormsEncryptor {

  /**
   * The encryption Service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  private EncryptServiceInterface $encryptionService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;


  public function __construct(EncryptServiceInterface $encryptService, EntityTypeManagerInterface $entityTypeManager) {
    $this->encryptionService = $encryptService;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param string $value
   *   The value that should be encrypted.
   * @param string $element
   *   The element.
   * @param string $webformId
   *   The webform id.
   *
   * @return string
   *   The encrypted string if element is configured to be encrypted.
   */
  public function encryptValue(string $value, string $element, string $webformId): string {
    /** @var WebformInterface $webform */
    $webform = $this->entityTypeManager->getStorage('webform')->load($webformId);

    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');
    $encryption_profile = isset($config[$element]) ? EncryptionProfile::load($config[$element]['encrypt_profile']) : FALSE;

    if (!$encryption_profile) {
      return $value;
    }

    $encrypted_data = [
      'data' => base64_encode($this->encryptionService->encrypt($value, $encryption_profile)),
      'encrypt_profile' => $encryption_profile->id(),
    ];

    return serialize($encrypted_data);

  }

}
