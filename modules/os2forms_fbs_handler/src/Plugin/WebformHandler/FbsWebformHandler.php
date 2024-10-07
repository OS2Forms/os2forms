<?php

namespace Drupal\os2forms_fbs_handler\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\os2forms_fbs_handler\Plugin\AdvancedQueue\JobType\FbsCreateUser;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fbs Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_fbs",
 *   label = @Translation("FBS"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Adds user to fbs"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class FbsWebformHandler extends WebformHandlerBase {
  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * The queue id.
   */
  private const QUEUE_ID = 'os2forms_fbs_handler';

  /**
   * Constructs an FbsWebformHandler object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelFactoryInterface $loggerFactory,
    ConfigFactoryInterface $configFactory,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entityTypeManager,
    WebformSubmissionConditionsValidatorInterface $conditionsValidator,
    WebformTokenManagerInterface $tokenManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->loggerFactory = $loggerFactory;
    $this->configFactory = $configFactory;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->conditionsValidator = $conditionsValidator;
    $this->tokenManager = $tokenManager;
    $this->submissionLogger = $loggerFactory->get('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if (is_null($this->getQueue())) {
      $form['queue_message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [$this->t('Cannot get queue @queue_id', ['@queue_id' => $this::QUEUE_ID])],
        ],
      ];
    }

    $translation_options = ['context' => 'FBS configuration'];

    $form['wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('FBS configuration', [], $translation_options),
      '#tree' => TRUE,
    ];

    $form['wrapper']['agency_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISIL', [], $translation_options),
      '#description' => $this->t('The library\'s ISIL number (e.g. "DK-775100" for Aarhus libraries', [], $translation_options),
      '#required' => TRUE,
      '#default_value' => $this->configuration['agency_id'] ?? '',
    ];

    $form['wrapper']['endpoint_url'] = [
      '#type' => 'url',
      '#title' => $this->t('FBS endpoint URL', [], $translation_options),
      '#description' => $this->t('The URL for the FBS REST service, usually something like https://et.cicero-fbs.com/rest'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['endpoint_url'] ?? 'https://cicero-fbs.com/rest/',
    ];

    $form['wrapper']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username', [], $translation_options),
      '#description' => $this->t('FBS username to allow connection to FBS'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['username'] ?? '',
    ];

    $form['wrapper']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password', [], $translation_options),
      '#description' => $this->t('Password to access the API'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['password'] ?? '',
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['agency_id'] = $form_state
      ->getValue(['wrapper', 'agency_id']);
    $this->configuration['endpoint_url'] = $form_state
      ->getValue(['wrapper', 'endpoint_url']);
    $this->configuration['username'] = $form_state
      ->getValue(['wrapper', 'username']);
    $this->configuration['password'] = $form_state
      ->getValue(['wrapper', 'password']);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void {
    $logger_context = [
      'handler_id' => 'os2forms_fbs',
      'channel' => 'webform_submission',
      'webform_submission' => $webform_submission,
      'operation' => 'submission queued',
    ];

    // Validate fields required in the job and FBS client.
    $data = $webform_submission->getData();
    $fields = [
      'afhentningssted',
      'barn_cpr',
      'barn_mail',
      'cpr',
      'email',
      'navn',
      'pinkode',
    ];
    foreach ($fields as $field) {
      if (!isset($data[$field])) {
        $this->submissionLogger->error($this->t('Missing field in submission @field to queue for processing', ['@field' => $field]), $logger_context);
        return;
      }
    }

    /** @var \Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $this->getQueue();
    $job = Job::create(FbsCreateUser::class, [
      'submissionId' => $webform_submission->id(),
      'configuration' => $this->configuration,
    ]);
    $queue->enqueueJob($job);

    $this->submissionLogger->notice($this->t('Added submission #@serial to queue for processing', ['@serial' => $webform_submission->serial()]), $logger_context);
  }

  /**
   * Get queue.
   */
  private function getQueue(): ?Queue {
    $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var ?\Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $queueStorage->load($this::QUEUE_ID);

    return $queue;
  }

}
