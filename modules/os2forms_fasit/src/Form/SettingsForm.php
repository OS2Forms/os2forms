<?php

namespace Drupal\os2forms_fasit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_fasit\Helper\CertificateLocatorHelper;
use Drupal\os2forms_fasit\Helper\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;

/**
 * Organisation settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  public const FASIT_API_BASE_URL = 'fasit_api_base_url';
  public const FASIT_API_TENANT = 'fasit_api_tenant';
  public const FASIT_API_VERSION = 'fasit_api_version';
  public const CERTIFICATE = 'certificate';

  /**
   * Constructor.
   */
  public function __construct(private readonly Settings $settings, private readonly CertificateLocatorHelper $certificateLocatorHelper) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SettingsForm {
    return new static(
      $container->get(Settings::class),
      $container->get(CertificateLocatorHelper::class)
    );
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

    $fasitApiBaseUrl = $this->settings->getFasitApiBaseUrl();
    $form[self::FASIT_API_BASE_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fasit API base url'),
      '#required' => TRUE,
      '#default_value' => !empty($fasitApiBaseUrl) ? $fasitApiBaseUrl : NULL,
      '#description' => $this->t('Specifies which base url to use. This is disclosed by Schultz'),
    ];

    $fasitApiTenant = $this->settings->getFasitApiTenant();
    $form[self::FASIT_API_TENANT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fasit API tenant'),
      '#required' => TRUE,
      '#default_value' => !empty($fasitApiTenant) ? $fasitApiTenant : NULL,
      '#description' => $this->t('Specifies which tenant to use. This is disclosed by Schultz'),
    ];

    $fasitApiVersion = $this->settings->getFasitApiVersion();
    $form[self::FASIT_API_VERSION] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fasit API version'),
      '#required' => TRUE,
      '#default_value' => !empty($fasitApiVersion) ? $fasitApiVersion : NULL,
      '#description' => $this->t('Specifies which api version to use. Should probably be v2'),
    ];

    $certificate = $this->settings->getCertificate();

    $form[self::CERTIFICATE] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Certificate'),
      '#tree' => TRUE,

      CertificateLocatorHelper::LOCATOR_TYPE => [
        '#type' => 'select',
        '#title' => $this->t('Certificate locator type'),
        '#options' => [
          CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT => $this->t('Azure key vault'),
          CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM => $this->t('File system'),
        ],
        '#default_value' => $certificate[CertificateLocatorHelper::LOCATOR_TYPE] ?? NULL,
      ],
    ];

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Azure key vault'),
      '#states' => [
        'visible' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT]],
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
        '#default_value' => $certificate[CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] ?? NULL,
        '#states' => [
          'required' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT]],
        ],
      ];
    }

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File system'),
      '#states' => [
        'visible' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]],
      ],

      'path' => [
        '#type' => 'textfield',
        '#title' => $this->t('Path'),
        '#default_value' => $certificate[CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL,
        '#states' => [
          'required' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]],
        ],
      ],
    ];

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_PASSPHRASE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Passphrase'),
      '#default_value' => $certificate[CertificateLocatorHelper::LOCATOR_PASSPHRASE] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    $form['actions']['testCertificate'] = [
      '#type' => 'submit',
      '#name' => 'testCertificate',
      '#value' => $this->t('Test certificate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $formState): void {
    $triggeringElement = $formState->getTriggeringElement();
    if ('testCertificate' === ($triggeringElement['#name'] ?? NULL)) {
      return;
    }

    $values = $formState->getValues();

    if (CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM === $values[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE]) {
      $path = $values[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL;
      if (!file_exists($path)) {
        $formState->setErrorByName('certificate][file_system][path', $this->t('Invalid certificate path: %path', ['%path' => $path]));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
    $triggeringElement = $formState->getTriggeringElement();
    if ('testCertificate' === ($triggeringElement['#name'] ?? NULL)) {
      $this->testCertificate();
      return;
    }

    try {
      $settings[self::CERTIFICATE] = $formState->getValue(self::CERTIFICATE);
      $settings[self::FASIT_API_BASE_URL] = $formState->getValue(self::FASIT_API_BASE_URL);
      $settings[self::FASIT_API_TENANT] = $formState->getValue(self::FASIT_API_TENANT);
      $settings[self::FASIT_API_VERSION] = $formState->getValue(self::FASIT_API_VERSION);

      $this->settings->setSettings($settings);
      $this->messenger()->addStatus($this->t('Settings saved'));
    }
    catch (OptionsResolverException $exception) {
      $this->messenger()->addError($this->t('Settings not saved (@message)', ['@message' => $exception->getMessage()]));

      return;
    }

    $this->messenger()->addStatus($this->t('Settings saved'));
  }

  /**
   * Test certificate.
   */
  private function testCertificate(): void {
    try {
      $certificateLocator = $this->certificateLocatorHelper->getCertificateLocator();
      $certificateLocator->getCertificates();
      $this->messenger()->addStatus($this->t('Certificate successfully tested'));
    }
    catch (\Throwable $throwable) {
      $message = $this->t('Error testing certificate: %message', ['%message' => $throwable->getMessage()]);
      $this->messenger()->addError($message);
    }
  }

}
