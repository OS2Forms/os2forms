<?php

namespace Drupal\os2forms_fasit\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\advancedqueue\Job;
use Drupal\os2forms_fasit\Plugin\AdvancedQueue\JobType\Fasit;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fasit Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_fasit",
 *   label = @Translation("Fasit"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Forwards to Fasit."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FasitWebformHandler extends WebformHandlerBase {
  public const FASIT_HANDLER_GENERAL = 'general';
  public const FASIT_HANDLER_DOCUMENT_TITLE = 'document_title';
  public const FASIT_HANDLER_DOCUMENT_DESCRIPTION = 'document_description';
  public const FASIT_HANDLER_CPR_ELEMENT = 'cpr_element';
  public const FASIT_HANDLER_ATTACHMENT_ELEMENT = 'attachment_element';

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * Constructs a FasitWebformHandler object.
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $loggerFactory, ConfigFactoryInterface $configFactory, RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager, WebformSubmissionConditionsValidatorInterface $conditionsValidator, WebformTokenManagerInterface $tokenManager) {
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
   *
   * @phpstan-param array<string, mixed> $configuration
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
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    $form[self::FASIT_HANDLER_GENERAL] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
    ];

    $form[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_DOCUMENT_TITLE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document title'),
      '#description' => $this->t('Fasit document title'),
      '#default_value' => $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_DOCUMENT_TITLE] ?? '',
      '#required' => TRUE,
    ];

    $form[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_DOCUMENT_DESCRIPTION] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document description'),
      '#description' => $this->t('Fasit document description'),
      '#default_value' => $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_DOCUMENT_DESCRIPTION] ?? '',
      '#required' => TRUE,
    ];

    $form[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_CPR_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('CPR element'),
      '#options' => $this->getAvailableElementsByType(['textfield', 'os2forms_nemid_cpr', 'os2forms_person_lookup'], $elements),
      '#default_value' => $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_CPR_ELEMENT] ?? '',
      '#description' => $this->t('Choose element containing CPR.'),
      '#required' => TRUE,
      '#size' => 5,
    ];

    $form[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_ATTACHMENT_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment element'),
      '#options' => $this->getAvailableElementsByType(['os2forms_attachment'], $elements),
      '#default_value' => $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_ATTACHMENT_ELEMENT] ?? '',
      '#description' => $this->t('Choose the element responsible for creating the submission attachment.'),
      '#required' => TRUE,
      '#size' => 5,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_DOCUMENT_TITLE] = $form_state->getValue(self::FASIT_HANDLER_GENERAL)[self::FASIT_HANDLER_DOCUMENT_TITLE];
    $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_DOCUMENT_DESCRIPTION] = $form_state->getValue(self::FASIT_HANDLER_GENERAL)[self::FASIT_HANDLER_DOCUMENT_DESCRIPTION];
    $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_CPR_ELEMENT] = $form_state->getValue(self::FASIT_HANDLER_GENERAL)[self::FASIT_HANDLER_CPR_ELEMENT];
    $this->configuration[self::FASIT_HANDLER_GENERAL][self::FASIT_HANDLER_ATTACHMENT_ELEMENT] = $form_state->getValue(self::FASIT_HANDLER_GENERAL)[self::FASIT_HANDLER_ATTACHMENT_ELEMENT];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void {
    $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var \Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $queueStorage->load('fasit_queue');
    $job = Job::create(Fasit::class, [
      'submissionId' => $webform_submission->id(),
      'handlerConfiguration' => $this->configuration,
    ]);
    $queue->enqueueJob($job);

    $logger_context = [
      'handler_id' => 'os2forms_fasit',
      'channel' => 'webform_submission',
      'webform_submission' => $webform_submission,
      'operation' => 'submission queued',
    ];

    $this->submissionLogger->notice($this->t('Added submission #@serial to queue for processing', ['@serial' => $webform_submission->serial()]), $logger_context);
  }

  /**
   * Get available elements by type.
   *
   * @phpstan-param array<mixed, mixed> $types
   * @phpstan-param array<string, mixed> $elements
   * @phpstan-return array<string, mixed>
   */
  private function getAvailableElementsByType(array $types, array $elements): array {
    $attachmentElements = array_filter($elements, function ($element) use ($types) {
      return in_array($element['#type'], $types);
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

}
