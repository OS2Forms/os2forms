<?php

namespace Drupal\os2forms_encrypt\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\os2forms_encrypt\Form\SettingsForm;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 *
 * @package Drupal\os2forms_encrypt\Commands
 */
class Os2FormsEncryptCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Factory to get module configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An instance of the entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   An instance of the config factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    parent::__construct();
  }

  /**
   * Enable encrypt for all existing webform elements.
   *
   * @command os2forms-encrypt:enable
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enabledEncrypt(): void {
    $config = $this->configFactory->get(SettingsForm::$configName);
    if (!$config->get('enabled')) {
      $this->output()->writeln('Encrypt has not been enabled.');
      return;
    }

    $defaultEncryptionProfile = $config->get('default_encryption_profile');

    // Get the storage for Webform entity type.
    $webformStorage = $this->entityTypeManager->getStorage('webform');

    // Load all webform entities.
    $webforms = $webformStorage->loadMultiple();

    /** @var \Drupal\webform\Entity\Webform $webform */
    foreach ($webforms as $webform) {
      $elements = $webform->getElementsDecodedAndFlattened();
      $config = $webform->getThirdPartySettings('webform_encrypt');

      $changed = FALSE;
      foreach ($elements as $key => $element) {
        if (!isset($config['element'][$key]) || $config['element'][$key]['encrypt_profile'] === NULL) {
          $config['element'][$key] = [
            'encrypt' => TRUE,
            'encrypt_profile' => $defaultEncryptionProfile,
          ];
          $changed = TRUE;
        }
      }

      // Save the webform entity so the changes persist, if any changes where
      // made.
      if ($changed) {
        $webform->setThirdPartySetting('webform_encrypt', 'element', $config['element']);
        $webform->save();
      }
    }
  }

}
