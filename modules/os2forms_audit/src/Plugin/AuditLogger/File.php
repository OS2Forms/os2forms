<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\ConfigurableInterface;
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
class File extends PluginBase implements AuditLoggerInterface, PluginFormInterface, ConfigurableInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function log(string $type, int $timestamp, string $line, array $metadata = []): void {
    // Code to write the entity to a file.
    // This is just a placeholder and won't write the data.
    file_put_contents(
      $this->configuration['file'],
      json_encode([
        'type' => $type,
        'epoc' => $timestamp,
        'line' => $line,
        'metadata' => $metadata,
      ]) . PHP_EOL,
      FILE_APPEND | LOCK_EX);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'file' => '/tmp/os2forms_audit.log',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $form['file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The complete path and name of the file where log entries are stored.'),
      '#default_value' => $this->configuration['file'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValues();
      $configuration = [
        'file' => $values['file'],
      ];
      $this->setConfiguration($configuration);
    }
  }

}
