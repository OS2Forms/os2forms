<?php

namespace Drupal\os2forms_audit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\os2forms_audit\Form\PluginSettingsForm;
use Drupal\os2forms_audit\Form\SettingsForm;
use Drupal\os2forms_audit\Plugin\LoggerManager;

/**
 * Class Logger.
 *
 * Helper service to send log messages in the right direction.
 */
class Logger {

  public function __construct(
    private readonly LoggerManager $loggerManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Logs a message using a plugin-specific logger.
   *
   * @param string $type
   *   The type of event to log (auth, lookup etc.)
   * @param int $timestamp
   *   The timestamp for the log message.
   * @param string $line
   *   The log message.
   * @param array $metadata
   *   Additional metadata for the log message. Default is an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function log(string $type, int $timestamp, string $line, array $metadata = []): void {
    $config = $this->configFactory->get(SettingsForm::$configName);
    $plugin_id = $config->get('provider');

    $configuration = $this->configFactory->get(PluginSettingsForm::getConfigName())->get($plugin_id);
    $logger = $this->loggerManager->createInstance($plugin_id, $configuration ?? []);

    $logger->log($type, $timestamp, $line, $metadata);
  }

}
