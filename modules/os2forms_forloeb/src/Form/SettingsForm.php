<?php

namespace Drupal\os2forms_forloeb\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for os2forms_forloeb.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'os2forms_forloeb.settings';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    readonly private RoleStorageInterface $roleStorage,
    readonly private EntityStorageInterface $queueStorage,
    readonly private ModuleExtensionList $moduleHandler
  ) {
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('entity_type.manager')->getStorage('advancedqueue_queue'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_forloeb_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $roles = $this->roleStorage->loadMultiple();
    $form['known_anonymous_roles'] = [
      '#title' => $this->t('Known anonymous roles'),
      '#type' => 'checkboxes',
      '#options' => array_map(static fn (RoleInterface $role) => $role->label(), $roles),
      '#default_value' => $config->get('known_anonymous_roles') ?: [],
      '#description' => $this->t('Roles that can act as “known anonymous”'),
    ];

    $form['processing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing'),
      '#tree' => TRUE,
    ];

    $defaultValue = $config->get('processing')['queue'] ?? NULL;
    $form['processing']['queue'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Queue'),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $this->queueStorage->loadMultiple()
      ),
      '#default_value' => $defaultValue,
      '#description' => $this->t("Queue for handling notification jobs. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code> (in a cron job).", [
        '@queue' => $defaultValue,
        ':queue_url' => '/admin/config/system/queues/jobs/' . urlencode($defaultValue ?? ''),
      ]),
    ];

    $form['templates'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Templates'),
      '#tree' => TRUE,
    ];

    $templatePath = $this->moduleHandler->getPath('os2forms_forloeb') . '/templates/os2forms-forloeb-notification-message-email-html.html.twig';
    $defaultTemplate = file_exists($templatePath) ? file_get_contents($templatePath) : NULL;
    $form['templates']['notification_email'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#required' => TRUE,
      '#title' => $this->t('Email template'),
      '#default_value' => $config->get('templates')['notification_email'] ?? $defaultTemplate,
      '#description' => $this->t('HTML template for email notifications. If the template is a path, e.g. <code>@templatePath</code>, the template will be loaded from this path.', [
        '@templatePath' => $templatePath,
      ]),
    ];

    $templatePath = $this->moduleHandler->getPath('os2forms_forloeb') . '/templates/os2forms-forloeb-notification-message-pdf-html.html.twig';
    $defaultTemplate = file_exists($templatePath) ? file_get_contents($templatePath) : NULL;
    $form['templates']['notification_pdf'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#required' => TRUE,
      '#title' => $this->t('PDF template'),
      '#default_value' => $config->get('templates')['notification_pdf'] ?? $defaultTemplate,
      '#description' => $this->t('HTML template for PDF notifications (digital post). If the template is a path, e.g. <code>@templatePath</code>, the template will be loaded from this path.', [
        '@templatePath' => $templatePath,
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $this->config(static::SETTINGS)
      ->set('known_anonymous_roles', $formState->getValue('known_anonymous_roles'))
      ->set('processing', $formState->getValue('processing'))
      ->set('templates', $formState->getValue('templates'))
      ->save();

    parent::submitForm($form, $formState);
  }

}
