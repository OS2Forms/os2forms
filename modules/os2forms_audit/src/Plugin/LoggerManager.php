<?php

namespace Drupal\os2forms_audit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Logger plugin manager.
 *
 * @see \Drupal\os2forms_audit\Annotation\AuditLoggerProvider
 * @see \Drupal\os2forms_audit\Plugin\AuditLogger\AuditLoggerInterface
 * @see plugin_api
 */
class LoggerManager extends DefaultPluginManager {

  /**
   * Constructor for LoggerManager objects.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AuditLogger',
      $namespaces,
      $module_handler,
      'Drupal\os2forms_audit\Plugin\AuditLogger\AuditLoggerInterface',
      'Drupal\os2forms_audit\Annotation\AuditLoggerProvider',
    );

    $this->alterInfo('os2forms_audit_logger_info');
    $this->setCacheBackend($cache_backend, 'os2forms_audit_logger_plugins');
  }

}
