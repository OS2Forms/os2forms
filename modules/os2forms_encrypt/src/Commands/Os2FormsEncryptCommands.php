<?php

namespace Drupal\os2forms_encrypt\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\os2forms_encrypt\Form\SettingsForm;
use Drupal\webform\Plugin\WebformElementManagerInterface;
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
  protected $entityTypeManager;

  /**
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  private WebformElementManagerInterface $webformElementManager;

  /**
   * Constructs a new Os2FormsEncryptCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, WebformElementManagerInterface $webformElementManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->webformElementManager = $webformElementManager;
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
    $config = \Drupal::config(SettingsForm::$configName);
    if (!$config->get('enabled')) {
      $this->output()->writeln('Encrypt has not been enabled.');
      return;
    }

    // Get the storage for Webform entity type.
    $webformStorage = $this->entityTypeManager->getStorage('webform');

    // Load all webform entities.
    $webforms = $webformStorage->loadMultiple();

    /** @var \Drupal\webform\Entity\Webform $webform */
    foreach ($webforms as $webform) {
      // This will give you an associative array of all elements in the current webform.
      $elements = $webform->getElementsDecoded();
      $config = $webform->getThirdPartySettings('webform_encrypt');

      foreach ($elements as $key => $element) {
        if (!isset($config['element'][$key])) {
          $config['element'][$key] = [
            'encrypt' => TRUE,
            'encrypt_profile' => 'webform',
          ];
        }
      }

      // After modifying the element array, encode it back into a string and set it back on the webform entity.
      $webform->setThirdPartySetting('webform_encrypt', 'element', $config['element']);

      // Save the webform entity so the changes persist.
      $webform->save();
    }
  }
}
