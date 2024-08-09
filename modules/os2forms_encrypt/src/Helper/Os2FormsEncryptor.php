<?php

namespace Drupal\os2forms_encrypt\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\os2forms_encrypt\Form\SettingsForm;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * The Os2FormsEncryptor class.
 */
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

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  public function __construct(EncryptServiceInterface $encryptService, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->encryptionService = $encryptService;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Encrypts value if element is configured to be encrypted.
   *
   * @param string $value
   *   The value that should be encrypted.
   * @param string $element
   *   The element.
   * @param string $webformId
   *   The webform id.
   *
   * @return string
   *   The resulting value.
   */
  public function encryptValue(string $value, string $element, string $webformId): string {
    /** @var \Drupal\webform\WebformInterface $webform */
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

  /**
   * Enables encrypt on all elements of webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   */
  public function enableEncryption(WebformInterface $webform): void {

    // Check that encryption is enabled.
    $config = $this->configFactory->get(SettingsForm::$configName);
    if (!$config->get('enabled') || !$webform instanceof Webform) {
      return;
    }

    // Check that there are any elements to enable encryption on.
    $elements = $webform->getElementsDecodedAndFlattened();

    if (empty($elements)) {
      return;
    }

    $encryptedElements = array_map(static fn () => [
      'encrypt' => TRUE,
      'encrypt_profile' => $config->get('default_encryption_profile'),
    ], $elements);

    $webform->setThirdPartySetting('webform_encrypt', 'element', $encryptedElements);
  }

}
