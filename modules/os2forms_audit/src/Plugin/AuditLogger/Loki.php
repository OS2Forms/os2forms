<?php

namespace Drupal\os2forms_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Stores entities in the database.
 *
 * @AuditLoggerProvider(
 *   id = "loki",
 *   title = @Translation("Grafana Loki"),
 *   description = @Translation("Store entity data in Loki.")
 * )
 */
class Loki extends PluginBase implements AuditLoggerInterface, PluginFormInterface, ConfigurableInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function log(int $timestamp, string $line, array $metadata = []): void {
    // @todo use loki client to send message to loki
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
      'entrypoint' => 'http://loki:3100',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['entrypoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Entry Point URL'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['entrypoint'],
    ];

    $form['auth'] = [
      '#tree' => TRUE,
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#required' => TRUE,
        '#default_value' => $this->configuration['auth']['username'],
      ],
      'password' => [
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#required' => TRUE,
        '#default_value' => $this->configuration['auth']['password'],
      ],
    ];

    $form['curl_options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('cURL Options'),
      '#default_value' => $this->configuration['curl_options'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    // Validate entrypoint.
    if (filter_var($values['entrypoint'], FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('entrypoint', $this->t('Invalid URL.'));
    }

    // Validate auth username.
    if (empty($values['auth']['username'])) {
      $form_state->setErrorByName('auth][username', $this->t('Username is required.'));
    }

    // Validate auth password.
    if (empty($values['auth']['password'])) {
      $form_state->setErrorByName('auth][password', $this->t('Password is required.'));
    }

    $curlOptions = array_filter(explode(',', $values['curl_options']));
    foreach ($curlOptions as $option) {
      [$key] = explode(' =>', $option);
      $key = trim($key);
      if (!defined($key)) {
        $form_state->setErrorByName('curl_options', $this->t('%option is not a valid cURL option.', ['%option' => $key]));
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValues();
      $configuration = [
        'entrypoint' => $values['entrypoint'],
        'auth' => [
          'username' => $values['auth']['username'],
          'password' => $values['auth']['password'],
        ],
        'curl_options' => $values['curl_options'],
      ];
      $this->setConfiguration($configuration);
    }
  }

}
