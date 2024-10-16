<?php

namespace Drupal\os2forms_digital_post\Helper;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\os2forms_digital_post\Exception\InvalidRecipientIdentifierElementException;
use Drupal\os2forms_digital_post\Exception\RuntimeException;
use Drupal\os2forms_digital_post\Exception\SubmissionNotFoundException;
use Drupal\os2forms_digital_post\Plugin\AdvancedQueue\JobType\SendDigitalPostSF1601;
use Drupal\os2forms_digital_post\Plugin\WebformHandler\WebformHandlerSF1601;
use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCompany;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCpr;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use ItkDev\Serviceplatformen\Service\SF1601\SF1601;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Webform helper.
 */
final class WebformHelperSF1601 implements LoggerInterface {
  use LoggerTrait;

  public const RECIPIENT_IDENTIFIER_TYPE = 'recipient_identifier_type';
  public const RECIPIENT_IDENTIFIER = 'recipient_identifier';

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly Settings $settings,
    EntityTypeManagerInterface $entityTypeManager,
    private readonly DataLookupManager $dataLookupManager,
    private readonly MeMoHelper $meMoHelper,
    private readonly ForsendelseHelper $forsendelseHelper,
    private readonly BeskedfordelerHelper $beskedfordelerHelper,
    private readonly LoggerChannelInterface $logger,
    private readonly LoggerChannelInterface $submissionLogger,
    private readonly DigitalPostHelper $digitalPostHelper,
  ) {
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * Send digital post.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission.
   * @param array $handlerSettings
   *   The Handler settings.
   * @param array $submissionData
   *   Submission data. Only for overriding during testing and development.
   *
   * @return array
   *   [The response, The kombi post message].
   *
   * @phpstan-param array<string, mixed> $handlerSettings
   * @phpstan-param array<string, mixed> $submissionData
   * @phpstan-return array<int, mixed>
   */
  public function sendDigitalPost(WebformSubmissionInterface $submission, array $handlerSettings, array $submissionData = []): array {
    $submissionData = $submissionData + $submission->getData();

    $handlerMessageSettings = $handlerSettings[WebformHandlerSF1601::MEMO_MESSAGE];
    $recipientIdentifierKey = $handlerMessageSettings[WebformHandlerSF1601::RECIPIENT_ELEMENT] ?? NULL;
    if (NULL === $recipientIdentifierKey) {
      $message = 'Recipient identifier element (key: @element_key) not found in submission';
      $context = [
        '@element_key' => WebformHandlerSF1601::RECIPIENT_ELEMENT,
      ];

      $this->error($message, $context);
      throw new InvalidRecipientIdentifierElementException(str_replace(array_keys($context), array_values($context),
        $message));
    }

    $recipientIdentifier = $submissionData[$recipientIdentifierKey] ?? NULL;

    // Fix if os2forms_person_lookup (cpr & name validation) element is used.
    if (is_array($recipientIdentifier)) {
      // Example:
      // [
      // 'cpr_number' => 1234567890,
      // 'name' => Eksempel Eksempelsen,
      // ].
      $recipientIdentifier = $recipientIdentifier['cpr_number'] ?? NULL;
    }

    if (NULL === $recipientIdentifier) {
      $message = 'Recipient identifier element (key: @element_key) not found in submission';
      $context = [
        '@element_key' => WebformHandlerSF1601::RECIPIENT_ELEMENT,
      ];

      $this->error($message, $context);
      throw new InvalidRecipientIdentifierElementException(str_replace(array_keys($context), array_values($context),
        $message));
    }

    // Remove all non-digits from recipient identifier.
    $recipientIdentifier = preg_replace('/[^\d]+/', '', $recipientIdentifier);

    /** @var \Drupal\os2web_datalookup\LookupResult\CprLookupResult|\Drupal\os2web_datalookup\LookupResult\CompanyLookupResult|null $lookupResult */
    $lookupResult = NULL;

    if (preg_match('/^\d{8}$/', $recipientIdentifier)) {
      $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cvr_lookup');
      if (!($instance instanceof DataLookupInterfaceCompany)) {
        throw new RuntimeException('Cannot get CVR data lookup instance');
      }
      $lookupResult = $instance->lookup($recipientIdentifier);
      if (!$lookupResult->isSuccessful()) {
        throw new RuntimeException('Cannot validate recipient CVR');
      }
      $recipientIdentifierType = 'CVR';
    }
    else {
      $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cpr_lookup');
      if (!($instance instanceof DataLookupInterfaceCpr)) {
        throw new RuntimeException('Cannot get CPR data lookup instance');
      }
      $lookupResult = $instance->lookup($recipientIdentifier);
      if (!$lookupResult->isSuccessful()) {
        throw new RuntimeException('Cannot validate recipient CPR');
      }
      $recipientIdentifierType = 'CPR';
    }

    $senderSettings = $this->settings->getSender();
    $messageOptions = [
      self::RECIPIENT_IDENTIFIER_TYPE => $recipientIdentifierType,
      self::RECIPIENT_IDENTIFIER => $recipientIdentifier,

      Settings::SENDER_IDENTIFIER_TYPE => $senderSettings[Settings::SENDER_IDENTIFIER_TYPE],
      Settings::SENDER_IDENTIFIER => $senderSettings[Settings::SENDER_IDENTIFIER],

      WebformHandlerSF1601::SENDER_LABEL => $handlerMessageSettings[WebformHandlerSF1601::SENDER_LABEL],
      WebformHandlerSF1601::MESSAGE_HEADER_LABEL => $handlerMessageSettings[WebformHandlerSF1601::MESSAGE_HEADER_LABEL],
    ];
    $message = $this->meMoHelper->buildWebformSubmissionMessage($submission, $messageOptions, $handlerSettings, $lookupResult);
    $forsendelseOptions = [
      self::RECIPIENT_IDENTIFIER_TYPE => $recipientIdentifierType,
      self::RECIPIENT_IDENTIFIER => $recipientIdentifier,

      Settings::SENDER_IDENTIFIER_TYPE => $senderSettings[Settings::SENDER_IDENTIFIER_TYPE],
      Settings::SENDER_IDENTIFIER => $senderSettings[Settings::SENDER_IDENTIFIER],
      Settings::FORSENDELSES_TYPE_IDENTIFIKATOR => $senderSettings[Settings::FORSENDELSES_TYPE_IDENTIFIKATOR],

      WebformHandlerSF1601::SENDER_LABEL => $handlerMessageSettings[WebformHandlerSF1601::SENDER_LABEL],
      WebformHandlerSF1601::MESSAGE_HEADER_LABEL => $handlerMessageSettings[WebformHandlerSF1601::MESSAGE_HEADER_LABEL],
    ];
    $forsendelse = $this->forsendelseHelper->buildSubmissionForsendelse($submission, $forsendelseOptions, $handlerSettings, $lookupResult);

    $type = $handlerMessageSettings[WebformHandlerSF1601::TYPE] ?? SF1601::TYPE_DIGITAL_POST;

    return $this->digitalPostHelper->sendDigitalPost($type, $message, $forsendelse, $submission);
  }

  /**
   * Load webform submission by id.
   */
  public function loadSubmission(int $id): ?WebformSubmissionInterface {
    return $this->webformSubmissionStorage->load($id);
  }

  /**
   * Load queue.
   */
  private function loadQueue(): QueueInterface {
    $processingSettings = $this->settings->getProcessing();

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->queueStorage->load($processingSettings['queue'] ?? 'os2forms_digital_post');

    return $queue;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $level
   *   The level.
   * @param string $message
   *   The message.
   * @param array $context
   *   The context.
   *
   * @phpstan-param array<string, mixed> $context
   */
  public function log($level, $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
    // @see https://www.drupal.org/node/3020595
    if (isset($context['webform_submission']) && $context['webform_submission'] instanceof WebformSubmissionInterface) {
      $this->submissionLogger->log($level, $message, $context);
    }
  }

  /**
   * Create a job.
   *
   * @see self::processJob()
   *
   * @phpstan-param array<string, mixed> $handlerConfiguration
   */
  public function createJob(WebformSubmissionInterface $webformSubmission, array $handlerConfiguration): ?Job {
    $context = [
      'handler_id' => 'os2forms_digital_post',
      'webform_submission' => $webformSubmission,
    ];

    try {
      $job = Job::create(SendDigitalPostSF1601::class, [
        'formId' => $webformSubmission->getWebform()->id(),
        'submissionId' => $webformSubmission->id(),
        'handlerConfiguration' => $handlerConfiguration,
      ]);
      $queue = $this->loadQueue();
      $queue->enqueueJob($job);
      $context['@queue'] = $queue->id();
      $this->notice('Job for sending digital post add to the queue @queue.', $context + [
        'operation' => 'digital post queued for sending',
      ]);

      return $job;
    }
    catch (\Exception $exception) {
      $this->error('Error creating job for sending digital post.', $context + [
        'operation' => 'digital post failed',
      ]);
      return NULL;
    }
  }

  /**
   * Process a job.
   *
   * @see self::createJob()
   */
  public function processJob(Job $job): JobResult {
    $payload = $job->getPayload();

    $context = [
      'handler_id' => 'os2forms_digital_post',
      'operation' => 'digital post send',
    ];
    try {
      $submissionId = $payload['submissionId'];
      $submission = $this->loadSubmission($submissionId);
      if (NULL === $submission) {
        $message = 'Cannot load submission @submissionId';
        $context = [
          '@submissionId' => $submissionId,
        ];
        $this->error($message, $context);

        throw new SubmissionNotFoundException(str_replace(array_keys($context), array_values($context),
          $message));
      }

      $context['webform_submission'] = $submission;
      $this->sendDigitalPost($submission, $payload['handlerConfiguration']);

      $this->notice('Digital post sent', $context);

      return JobResult::success();
    }
    catch (\Exception $e) {
      $this->error('Error: @message', $context + [
        '@message' => $e->getMessage(),
      ]);

      return JobResult::failure($e->getMessage());
    }
  }

  /**
   * Process Beskedfordeler data.
   *
   * @phpstan-param array<string, mixed> $data
   */
  public function processBeskedfordelerData(int $submissionId, array $data): void {
    $webformSubmission = $this->loadSubmission($submissionId);
    if (NULL !== $webformSubmission) {
      $context = [
        'webform_submission' => $webformSubmission,
        'handler_id' => 'os2forms_digital_post',
      ];
      $status = $data['TransaktionsStatusKode'];

      if (!empty($data['FejlDetaljer'])) {
        $this->error('@status; @error_code: @error_text', $context + [
          'operation' => 'digital post failed',
          '@status' => $status,
          '@error_code' => $data['FejlDetaljer']['FejlKode'],
          '@error_text' => $data['FejlDetaljer']['FejlTekst'],
          'data' => $data,
        ]);
      }
      else {
        $this->info('@status', $context + [
          'operation' => 'digital post success',
          '@status' => $status,
        ]);
      }
    }
  }

  /**
   * Proxy for BeskedfordelerHelper::deleteMessages().
   *
   * @see BeskedfordelerHelper::deleteMessages()
   *
   * @phpstan-param array<int, \Drupal\webform\WebformSubmissionInterface> $webformSubmissions
   */
  public function deleteMessages(array $webformSubmissions): void {
    $this->beskedfordelerHelper->deleteMessages($webformSubmissions);
  }

}
