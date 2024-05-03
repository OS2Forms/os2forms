<?php

namespace Drupal\os2forms_digital_post\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_digital_post\Helper\CertificateLocatorHelper;
use Drupal\os2forms_digital_post\Helper\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;

/**
 * Digital post settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  /**
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private readonly EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly Settings $settings,
    private readonly CertificateLocatorHelper $certificateLocatorHelper,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return self
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(Settings::class),
      $container->get(CertificateLocatorHelper::class),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_digital_post_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $this->settings->getTestMode(),
    ];

    $sender = $this->settings->getSender();
    $form['sender'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sender'),
      '#tree' => TRUE,

      Settings::SENDER_IDENTIFIER_TYPE => [
        '#type' => 'select',
        '#title' => $this->t('Identifier type'),
        '#options' => [
          'CVR' => $this->t('CVR'),
        ],
        '#default_value' => $sender[Settings::SENDER_IDENTIFIER_TYPE] ?? 'CVR',
        '#required' => TRUE,
      ],

      Settings::SENDER_IDENTIFIER => [
        '#type' => 'textfield',
        '#title' => $this->t('Identifier'),
        '#default_value' => $sender[Settings::SENDER_IDENTIFIER] ?? NULL,
        '#required' => TRUE,
      ],

      Settings::FORSENDELSES_TYPE_IDENTIFIKATOR => [
        '#type' => 'textfield',
        '#title' => $this->t('Forsendelsestypeidentifikator'),
        '#default_value' => $sender[Settings::FORSENDELSES_TYPE_IDENTIFIKATOR] ?? NULL,
        '#required' => TRUE,
      ],
    ];

    $certificate = $this->settings->getCertificate();
    $form['certificate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Certificate'),
      '#tree' => TRUE,

      'locator_type' => [
        '#type' => 'select',
        '#title' => $this->t('Certificate locator type'),
        '#options' => [
          'azure_key_vault' => $this->t('Azure key vault'),
          'file_system' => $this->t('File system'),
        ],
        '#default_value' => $certificate['locator_type'] ?? NULL,
      ],
    ];

    $form['certificate'][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Azure key vault'),
      '#states' => [
        'visible' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT]],
      ],
    ];

    $settings = [
      'tenant_id' => ['title' => $this->t('Tenant id')],
      'application_id' => ['title' => $this->t('Application id')],
      'client_secret' => ['title' => $this->t('Client secret')],
      'name' => ['title' => $this->t('Name')],
      'secret' => ['title' => $this->t('Secret')],
      'version' => ['title' => $this->t('Version')],
    ];

    foreach ($settings as $key => $info) {
      $form['certificate'][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] = [
        '#type' => 'textfield',
        '#title' => $info['title'],
        '#default_value' => $certificate[CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] ?? NULL,
        '#states' => [
          'required' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT]],
        ],
      ];
    }

    $form['certificate'][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM] = [
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

    $form['certificate']['passphrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Passphrase'),
      '#default_value' => $certificate['passphrase'] ?? NULL,
    ];

    $processing = $this->settings->getProcessing();
    $form['processing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing'),
      '#tree' => TRUE,
    ];

    $defaultValue = $processing['queue'] ?? 'os2forms_digital_post';
    $form['processing']['queue'] = [
      '#type' => 'select',
      '#title' => $this->t('Queue'),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $this->queueStorage->loadMultiple()
      ),
      '#default_value' => $defaultValue,
      '#description' => $this->t("Queue for digital post jobs. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code>(in a cron job).", [
        '@queue' => $defaultValue,
        ':queue_url' => '/admin/config/system/queues/jobs/' . urlencode($defaultValue),
      ]),
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
    if (CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM === $values['certificate']['locator_type']) {
      $path = $values['certificate'][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL;
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
      $settings['test_mode'] = (bool) $formState->getValue('test_mode');
      $settings['sender'] = $formState->getValue('sender');
      $settings['certificate'] = $formState->getValue('certificate');
      $settings['processing'] = $formState->getValue('processing');
      $this->settings->setSettings($settings);
      $this->messenger()->addStatus($this->t('Settings saved'));
    }
    catch (OptionsResolverException $exception) {
      $this->messenger()->addError($this->t('Settings not saved (@message)', ['@message' => $exception->getMessage()]));
    }
  }

  /**
   * Test certificate.
   */
  private function testCertificate(): void {
    try {
      $certificateLocator = $this->certificateLocatorHelper->getCertificateLocator();
      $certificateLocator->getCertificates();
      $this->messenger()->addStatus($this->t('Certificate succesfully tested'));
    }
    catch (\Throwable $throwable) {
      $message = $this->t('Error testing certificate: %message', ['%message' => $throwable->getMessage()]);
      $this->messenger()->addError($message);
    }
  }

}
