<?php

namespace Drupal\os2forms_fasit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_fasit\Helper\FasitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fasit settings form.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  public const CONFIG_NAME = 'os2forms_fasit.settings';
  public const FASIT_API_BASE_URL = 'fasit_api_base_url';
  public const FASIT_API_TENANT = 'fasit_api_tenant';
  public const FASIT_API_VERSION = 'fasit_api_version';
  public const CERTIFICATE = 'certificate';
  public const KEY = 'key';

  public const ACTION_PING_API = 'action_ping_api';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    private readonly FasitHelper $helper,
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return self
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get(FasitHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string>
   */
  protected function getEditableConfigNames() {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_fasit_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config(self::CONFIG_NAME);

    $form[self::FASIT_API_BASE_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fasit API base url'),
      '#required' => TRUE,
      '#default_value' => $config->get(self::FASIT_API_BASE_URL),
      '#description' => $this->t('Specifies which base url to use. This is disclosed by Schultz'),
    ];

    $form[self::FASIT_API_TENANT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fasit API tenant'),
      '#required' => TRUE,
      '#default_value' => $config->get(self::FASIT_API_TENANT),
      '#description' => $this->t('Specifies which tenant to use. This is disclosed by Schultz'),
    ];

    $form[self::FASIT_API_VERSION] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fasit API version'),
      '#required' => TRUE,
      '#default_value' => $config->get(self::FASIT_API_VERSION),
      '#description' => $this->t('Specifies which api version to use. Should probably be v2'),
    ];

    $form[self::KEY] = [
      '#type' => 'key_select',
      '#key_filters' => [
        'type' => 'os2web_key_certificate',
      ],
      '#title' => $this->t('Key'),
      '#required' => TRUE,
      '#default_value' => $config->get(self::KEY),
    ];

    $form['actions']['ping_api'] = [
      '#type' => 'container',

      self::ACTION_PING_API => [
        '#type' => 'submit',
        '#name' => self::ACTION_PING_API,
        '#value' => $this->t('Ping API'),
      ],

      'message' => [
        '#markup' => $this->t('Note: Pinging the API will use saved config.'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (self::ACTION_PING_API === ($form_state->getTriggeringElement()['#name'] ?? NULL)) {
      return;
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (self::ACTION_PING_API === ($form_state->getTriggeringElement()['#name'] ?? NULL)) {
      try {
        $this->helper->pingApi();
        $this->messenger()->addStatus($this->t('Pinged API successfully.'));
      }
      catch (\Throwable $t) {
        $this->messenger()->addError($this->t('Pinging API failed: @message', ['@message' => $t->getMessage()]));
      }
      return;
    }

    $config = $this->config(self::CONFIG_NAME);
    foreach ([
      self::FASIT_API_BASE_URL,
      self::FASIT_API_TENANT,
      self::FASIT_API_VERSION,
      self::KEY,
    ] as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
