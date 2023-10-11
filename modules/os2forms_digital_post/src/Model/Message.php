<?php

namespace Drupal\os2forms_digital_post\Model;

/**
 * A typed message.
 *
 * Holds data fetched from the BeskedfordelerHelper::TABLE_NAME database table.
 */
class Message {
  /**
   * The webform submission id.
   *
   * @var int
   */
  public int $submissionId;

  /**
   * The message UUID.
   *
   * @var string
   */
  public string $messageUUID;

  /**
   * The MeMo message (XML).
   *
   * @var string
   */
  public string $message;

  /**
   * The MeMo message receipt (XML).
   *
   * @var string
   */
  public string $receipt;

  /**
   * The Beskedfordeler message (XML).
   *
   * @var string
   */
  public ?string $beskedfordelerMessage;

  /**
   * Allow setting property using snake_case name.
   *
   * BeskedfordelerHelper::loadMessage() loads Messages via
   * Drupal\Core\Database::fetchObject() which in turn uses \PDO::FETCH_CLASS
   * (cf.
   * https://www.php.net/manual/en/pdostatement.fetch.php#refsect1-pdostatement.fetch-parameters)
   * so we need to translate from database column names (snake_case) to class
   * property names (camelCase).
   *
   * @see BeskedfordelerHelper::loadMessage()
   */
  public function __set(string $name, mixed $value): void {
    $property = [
      'submission_id' => 'submissionId',
      'message_uuid' => 'messageUUID',
      'beskedfordeler_message' => 'beskedfordelerMessage',
    ][$name] ?? $name;

    if (!property_exists($this, $property)) {
      throw new \RuntimeException(sprintf('Invalid property: %s', $property));
    }

    $this->$property = $value;
  }

}
