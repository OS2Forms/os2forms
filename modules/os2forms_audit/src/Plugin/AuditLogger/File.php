<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Writes entities to a file.
 *
 * @AuditLoggerProvider(
 *   id = "file",
 *   title = @Translation("File"),
 *   description = @Translation("Writes entities to a file.")
 * )
 */
class File extends PluginBase implements AuditLoggerInterface, PluginFormInterface, ConfigurableInterface{

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function write(EntityInterface $entity): void {
    // Code to write the entity to a file.
    // This is just a placeholder and won't write the data.
    file_put_contents('path_to_your_file.txt', serialize($entity));
  }

  public function getConfiguration(): array {
    return $this->configuration;
  }

  public function setConfiguration(array $configuration): static {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  public function defaultConfiguration(): array {
    return [
      'path' => '/tmp/os2forms_audit.log',
    ];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full path to the audit log file to store entries in'),
      '#default_value' => $this->configuration['path'],
    ];

    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement validateConfigurationForm() method.
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValues();
      $configuration = [
        'path' => $values['path'],
      ];
      $this->setConfiguration($configuration);
    }
  }
}
