<?php

namespace Drupal\os2forms_fasit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_fasit\Helper\CertificateLocatorHelper;
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
  public const CERTIFICATE_PROVIDER = 'certificate_provider';
  public const PROVIDER_TYPE_FORM = 'form';
  public const PROVIDER_TYPE_KEY = 'key';

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

    $certificateConfig = $config->get(self::CERTIFICATE) ?? [];

    $form[self::CERTIFICATE] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Certificate'),
      '#tree' => TRUE,

      self::CERTIFICATE_PROVIDER => [
        '#type' => 'select',
        '#title' => $this->t('Provider'),
        '#options' => [
          self::PROVIDER_TYPE_FORM => $this->t('Form'),
          self::PROVIDER_TYPE_KEY => $this->t('Key'),
        ],
        '#default_value' => $certificateConfig[self::CERTIFICATE_PROVIDER] ?? self::PROVIDER_TYPE_FORM,
        '#description' => $this->t('Specifies which provider to use'),
      ],
    ];

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE] = [
      '#type' => 'select',
      '#title' => $this->t('Certificate locator type'),
      '#options' => [
        CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT => $this->t('Azure key vault'),
        CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM => $this->t('File system'),
      ],
      '#default_value' => $certificateConfig[CertificateLocatorHelper::LOCATOR_TYPE] ?? NULL,
      '#states' => [
        'visible' => [':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_FORM]],
      ],
      '#description' => $this->t('Specifies which locator to use'),
    ];

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Azure key vault'),
      '#states' => [
        'visible' => [
          ':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_FORM],
          ':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT],
        ],
      ],
    ];

    $settings = [
      CertificateLocatorHelper::LOCATOR_AZURE_KEY_VAULT_TENANT_ID => ['title' => $this->t('Tenant id')],
      CertificateLocatorHelper::LOCATOR_AZURE_KEY_VAULT_APPLICATION_ID => ['title' => $this->t('Application id')],
      CertificateLocatorHelper::LOCATOR_AZURE_KEY_VAULT_CLIENT_SECRET => ['title' => $this->t('Client secret')],
      CertificateLocatorHelper::LOCATOR_AZURE_KEY_VAULT_NAME => ['title' => $this->t('Name')],
      CertificateLocatorHelper::LOCATOR_AZURE_KEY_VAULT_SECRET => ['title' => $this->t('Secret')],
      CertificateLocatorHelper::LOCATOR_AZURE_KEY_VAULT_VERSION => ['title' => $this->t('Version')],
    ];

    foreach ($settings as $key => $info) {
      $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] = [
        '#type' => 'textfield',
        '#title' => $info['title'],
        '#default_value' => $certificateConfig[CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] ?? NULL,
        '#states' => [
          'required' => [
            ':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_FORM],
            ':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT],
          ],
        ],
      ];
    }

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File system'),
      '#states' => [
        'visible' => [
          ':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_FORM],
          ':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM],
        ],
      ],

      'path' => [
        '#type' => 'textfield',
        '#title' => $this->t('Path'),
        '#default_value' => $certificateConfig[CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL,
        '#states' => [
          'required' => [
            ':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_FORM],
            ':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM],
          ],
        ],
      ],
    ];

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_PASSPHRASE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Passphrase'),
      '#default_value' => $certificateConfig[CertificateLocatorHelper::LOCATOR_PASSPHRASE] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_FORM],
        ],
      ],
    ];

    $form[self::CERTIFICATE][self::PROVIDER_TYPE_KEY] = [
      '#type' => 'key_select',
      '#key_filters' => [
        'type' => 'os2web_key_certificate',
      ],
      '#title' => $this->t('Key'),
      '#required' => TRUE,
      '#default_value' => $config->get(self::PROVIDER_TYPE_KEY),
      '#states' => [
        'visible' => [':input[name="certificate[certificate_provider]"]' => ['value' => self::PROVIDER_TYPE_KEY]],
      ],
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

    $values = $form_state->getValues();

    if (self::PROVIDER_TYPE_FORM === $values[self::CERTIFICATE][self::CERTIFICATE_PROVIDER]) {
      if (CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM === $values[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE]) {
        $path = $values[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL;
        if (!file_exists($path)) {
          $form_state->setErrorByName('certificate][file_system][path', $this->t('Invalid certificate path: %path', ['%path' => $path]));
        }
      }
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
      self::CERTIFICATE,
    ] as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
