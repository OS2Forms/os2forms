<?php

namespace Drupal\os2forms_digital_post\Helper;

use DigitalPost\MeMo\Message as MeMoMessage;
use Drupal\Core\Database\Connection;
use Drupal\os2forms_digital_post\Exception\InvalidMessageException;
use Drupal\os2forms_digital_post\Model\Message;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Beskedfordeler helper.
 */
class BeskedfordelerHelper {
  use LoggerAwareTrait;

  private const TABLE_NAME = 'os2forms_digital_post_beskedfordeler';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly MeMoHelper $meMoHelper,
    LoggerInterface $logger) {
    $this->setLogger($logger);
  }

  /**
   * Save MeMo message to the database.
   *
   * @throws \Exception
   */
  public function createMessage(int $submissionId, MeMoMessage $message, string $receipt): int {
    $messageUUID = $message->getMessageHeader()->getMessageUUID();
    $message = $this->meMoHelper->message2dom($message)->saveXML();

    return $this->database
      ->insert(self::TABLE_NAME)
      ->fields([
        'submission_id' => $submissionId,
        'message_uuid' => $messageUUID,
        'message' => $message,
        'receipt' => $receipt,
      ])
      ->execute();
  }

  /**
   * Load message.
   *
   * @param string $messageUUID
   *   The message's UUID.
   *
   * @return \Drupal\os2forms_digital_post\Model\Message|null
   *   The message.
   *
   * @throws \Exception
   *
   * @see Message::__set()
   */
  public function loadMessage(string $messageUUID): ?Message {
    // @phpstan-ignore-next-line (fetchObject invoked with 2 parameters)
    return $this->database
      ->select(self::TABLE_NAME, 'm')
      ->fields('m')
      ->condition('message_uuid', $messageUUID)
      ->execute()
      ->fetchObject(Message::class, []) ?: NULL;
  }

  /**
   * Add Beskedfordeler message to message.
   *
   * @throws \Exception
   */
  public function addBeskedfordelerMessage(string $messageUUID, string $beskedfordelerMessage): bool {
    $message = $this->loadMessage($messageUUID);

    if (NULL === $message) {
      throw new InvalidMessageException(sprintf('Invalid message UUID: %s', $messageUUID));
    }

    return $this->database
      ->update(self::TABLE_NAME)
      ->fields([
        'beskedfordeler_message' => $beskedfordelerMessage,
      ])
      ->condition('message_uuid', $messageUUID)
      ->execute() > 0;
  }

  /**
   * Delete messages for submissions.
   *
   * @param array|WebformSubmissionInterface[] $submissions
   *   The submissions.
   *
   * @throws \Exception
   */
  public function deleteMessages(array $submissions): void {
    $submissionIds = array_map(static function (WebformSubmissionInterface $submission) {
      return $submission->id();
    }, $submissions);

    $this->database
      ->delete(self::TABLE_NAME)
      ->condition('submission_id', $submissionIds, 'IN')
      ->execute();
  }

  /**
   * Implements hook_schema().
   *
   * @phpstan-return array<string, mixed>
   */
  public function schema(): array {
    return [
      self::TABLE_NAME => [
        'description' => 'OSForms digital post beskedfordeler',
        'fields' => [
          'submission_id' => [
            'description' => 'The submission id.',
            'type' => 'int',
            'not null' => TRUE,
          ],
          'message_uuid' => [
            'description' => 'The message UUID (formatted with dashes).',
            'type' => 'varchar',
            'length' => 36,
            'not null' => TRUE,
          ],
          'message' => [
            'description' => 'The MeMo message (XML).',
            'type' => 'text',
            'size' => 'medium',
            'not null' => TRUE,
          ],
          'receipt' => [
            'description' => 'The MeMo message receipt (XML).',
            'type' => 'text',
            'size' => 'medium',
            'not null' => TRUE,
          ],
          'beskedfordeler_message' => [
            'description' => 'The Beskedfordeler message (XML).',
            'type' => 'text',
            'size' => 'medium',
            'not null' => FALSE,
          ],
        ],
        'indexes' => [
          'submission_id' => ['submission_id'],
        ],
        'primary key' => ['message_uuid'],
      ],
    ];
  }

}
