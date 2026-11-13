<?php

namespace Drupal\os2forms_metadata;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Merge;

/**
 * Stores Webform metadata independently of configuration and submissions.
 */
class MetadataStorage {

  /**
   * Constructs the metadata storage service.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Loads a metadata row, or NULL if none exists.
   *
   * @return array<string, mixed>|null
   *   The stored metadata, if available.
   */
  public function load(string $uuid): ?array {
    return $this->loadMultiple([$uuid])[$uuid] ?? NULL;
  }

  /**
   * Loads metadata keyed by Webform UUID, omitting missing rows.
   *
   * @param string[] $uuids
   *   The Webform UUIDs to load.
   *
   * @return array<string, array<string, mixed>>
   *   The metadata rows.
   */
  public function loadMultiple(array $uuids): array {
    if (!$uuids) {
      return [];
    }

    $rows = $this->database->select('os2forms_metadata', 'metadata')
      ->fields('metadata')
      ->condition('webform_uuid', $uuids, 'IN')
      ->execute()
      ->fetchAllAssoc('webform_uuid');
    foreach ($rows as $uuid => $row) {
      $rows[$uuid] = (array) $row;
    }
    return $rows;
  }

  /**
   * Records creation once, including the initial last editor values.
   */
  public function create(string $uuid, ?int $uid, string $username): void {
    $timestamp = $this->time->getRequestTime();
    $result = $this->database->merge('os2forms_metadata')
      ->insertFields([
        'created' => $timestamp,
        'created_uid' => $uid,
        'created_username' => $username,
        'changed' => $timestamp,
        'changed_uid' => $uid,
        'changed_username' => $username,
      ])
      ->key('webform_uuid', $uuid)
      ->execute();
    if ($result === Merge::STATUS_INSERT) {
      $this->invalidate($uuid);
    }
  }

  /**
   * Records a form update without changing its creator or submission history.
   */
  public function update(string $uuid, ?int $uid, string $username): void {
    $this->database->merge('os2forms_metadata')
      ->key('webform_uuid', $uuid)
      ->fields([
        'changed' => $this->time->getRequestTime(),
        'changed_uid' => $uid,
        'changed_username' => $username,
      ])
      ->execute();
    $this->invalidate($uuid);
  }

  /**
   * Stores a completion timestamp supplied by the submission hooks.
   *
   * The caller decides whether the submission qualifies for tracking.
   */
  public function recordSubmission(string $uuid, int $timestamp): void {
    $this->database->merge('os2forms_metadata')
      ->key('webform_uuid', $uuid)
      ->fields(['latest_submission' => $timestamp])
      ->execute();
    $this->invalidate($uuid);
  }

  /**
   * Deletes metadata belonging to a deleted form.
   */
  public function delete(string $uuid): void {
    $this->database->delete('os2forms_metadata')
      ->condition('webform_uuid', $uuid)
      ->execute();
    $this->invalidate($uuid);
  }

  /**
   * Invalidates metadata displays for this form and the overview.
   *
   * The overview uses the list tag because submission activity does not save
   * the Webform and therefore does not invalidate its configuration cache tags.
   *
   * The settings details section uses the per-form tag for the same reason.
   */
  private function invalidate(string $uuid): void {
    $this->cacheTagsInvalidator->invalidateTags([
      'os2forms_metadata:' . $uuid,
      'os2forms_metadata_list',
    ]);
  }

}
