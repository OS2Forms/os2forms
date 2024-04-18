<?php

namespace Drupal\os2forms_audit\Form;

/**
 * @file
 * Abstract class for PluginSettingsForm implementation.
 */

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2web_datalookup\Form\PluginSettingsFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for PluginSettingsForm implementation.
 */
class PluginSettingsForm extends ConfigFormBase implements PluginSettingsFormInterface {

  /**
   * The manager to be used for instantiating plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected PluginManagerInterface $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
     ConfigFactoryInterface $config_factory,
     PluginManagerInterface $manager
  ) {
    parent::__construct($config_factory);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PluginSettingsForm|ConfigFormBase|static {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.os2forms_audit_logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getConfigName(): string {
    return 'os2forms_audit.plugin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [$this->getConfigName()];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return $this->getConfigName() . '_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $plugin_id = $form_state->getBuildInfo()['args'][0];
    $instance = $this->getPluginInstance($plugin_id);
    $form = $instance->buildConfigurationForm($form, $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $plugin_id = $form_state->getBuildInfo()['args'][0];
    $instance = $this->getPluginInstance($plugin_id);
    $instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $plugin_id = $form_state->getBuildInfo()['args'][0];
    $instance = $this->getPluginInstance($plugin_id);
    $instance->submitConfigurationForm($form, $form_state);

    $config = $this->config($this->getConfigName());
    $config->set($plugin_id, $instance->getConfiguration());
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns plugin instance for a given plugin id.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   *
   * @return object
   *   Plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPluginInstance(string $plugin_id): object {
    $configuration = $this->config($this->getConfigName())->get($plugin_id);

    return $this->manager->createInstance($plugin_id, $configuration ?? []);
  }

}
