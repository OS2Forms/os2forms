<?php

namespace Drupal\os2forms_digital_post\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\os2forms_digital_post\Helper\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Digital post settings form.
 */
final class SettingsForm extends ConfigFormBase {
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
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Settings $settings,
  ) {
    parent::__construct($config_factory);
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return self
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get(Settings::class),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string>
   */
  protected function getEditableConfigNames() {
    return [
      Settings::CONFIG_NAME,
    ];
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['message'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => [
          $this->t('Use <code>drush os2forms-digital-post:test:send</code> to test sending digital post.'),
        ],
      ],
    ];

    $form[Settings::TEST_MODE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $this->settings->getEditableValue(Settings::TEST_MODE),
      '#description' => $this->createDescription(Settings::TEST_MODE),
    ];

    $form[Settings::SENDER] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sender'),
      '#tree' => TRUE,

      Settings::SENDER_IDENTIFIER_TYPE => [
        '#type' => 'select',
        '#title' => $this->t('Identifier type'),
        '#options' => [
          'CVR' => $this->t('CVR'),
        ],
        '#default_value' => $this->settings->getEditableValue([Settings::SENDER, Settings::SENDER_IDENTIFIER_TYPE]) ?? 'CVR',
        '#required' => TRUE,
        '#description' => $this->createDescription([Settings::SENDER, Settings::SENDER_IDENTIFIER_TYPE]),
      ],

      Settings::SENDER_IDENTIFIER => [
        '#type' => 'textfield',
        '#title' => $this->t('Identifier'),
        '#default_value' => $this->settings->getEditableValue([Settings::SENDER, Settings::SENDER_IDENTIFIER]),
        '#required' => TRUE,
        '#description' => $this->createDescription([Settings::SENDER, Settings::SENDER_IDENTIFIER]),
      ],

      Settings::FORSENDELSES_TYPE_IDENTIFIKATOR => [
        '#type' => 'textfield',
        '#title' => $this->t('Forsendelsestypeidentifikator'),
        '#default_value' => $this->settings->getEditableValue([
          Settings::SENDER, Settings::FORSENDELSES_TYPE_IDENTIFIKATOR,
        ]),
        '#required' => TRUE,
        '#description' => $this->createDescription([Settings::SENDER, Settings::FORSENDELSES_TYPE_IDENTIFIKATOR]),
      ],
    ];

    $form[Settings::CERTIFICATE] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Certificate'),
      '#tree' => TRUE,

      Settings::KEY => [
        '#type' => 'key_select',
        '#key_filters' => [
          'type' => 'os2web_key_certificate',
        ],
        '#key_description' => FALSE,
        '#title' => $this->t('Key'),
        '#default_value' => $this->settings->getEditableValue([Settings::CERTIFICATE, Settings::KEY]),
        '#required' => TRUE,
        '#description' => $this->createDescription([Settings::CERTIFICATE, Settings::KEY]),
      ],
    ];

    $form[Settings::PROCESSING] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing'),
      '#tree' => TRUE,
    ];

    $queue = $this->settings->getEditableValue([Settings::PROCESSING, Settings::QUEUE]);
    $form[Settings::PROCESSING][Settings::QUEUE] = [
      '#type' => 'select',
      '#title' => $this->t('Queue'),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $this->queueStorage->loadMultiple()
      ),
      '#required' => TRUE,
      '#default_value' => $queue,
      '#description' => $this->createDescription([Settings::PROCESSING, Settings::QUEUE],
        $queue
          ? $this->t("Queue for digital post jobs. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code> (in a cron job).", [
            '@queue' => $queue,
            ':queue_url' => Url::fromRoute('view.advancedqueue_jobs.page_1', [
              'arg_0' => $queue,
            ])->toString(TRUE)->getGeneratedUrl(),
          ])
          : $this->t("Queue for digital post jobs. The queue must be processed via Drupal's cron or <code>drush advancedqueue:queue:process</code> (in a cron job)."),
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(Settings::CONFIG_NAME);
    foreach ([
      Settings::TEST_MODE,
      Settings::SENDER,
      Settings::CERTIFICATE,
      Settings::PROCESSING,
    ] as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Create form field description with information on any runtime override.
   *
   * @param string|array<string> $key
   *   The key.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   The actual field description.
   *
   * @return string
   *   The full description.
   */
  private function createDescription(string|array $key, ?TranslatableMarkup $description = NULL): string {
    if ($value = $this->settings->getOverride($key)) {
      if (!empty($description)) {
        $description .= '<br/>';
      }
      $description .= $this->t('<strong>Note</strong>: overridden on runtime with the value <code>@value</code>.', ['@value' => var_export($value['runtime'], TRUE)]);
    }

    return (string) $description;
  }

}
