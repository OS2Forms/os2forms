<?php

namespace Drupal\os2forms_metadata;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformEntityListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds metadata to the standard Webform overview.
 */
class WebformMetadataListBuilder extends WebformEntityListBuilder {

  /**
   * The metadata storage service.
   */
  protected MetadataStorage $metadataStorage;

  /**
   * The date formatter.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Metadata for the displayed forms, keyed by UUID.
   *
   * @var array<string, array<string, mixed>>
   */
  protected array $metadata = [];

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->metadataStorage = $container->get('os2forms_metadata.storage');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $webforms = parent::load();
    $this->loadMetadata($webforms);
    return $webforms;
  }

  /**
   * Loads all accessible forms, including archived forms and templates.
   *
   * @return \Drupal\webform\WebformInterface[]
   *   The accessible forms.
   */
  public function loadForExport(): array {
    /** @var \Drupal\webform\WebformEntityStorageInterface $storage */
    $storage = $this->getStorage();
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('title')
      ->sort('id')
      ->execute();
    $webforms = $storage->loadMultiple($ids);
    // Use the same form access rules as the standard Webform overview.
    if (!$this->isAdmin()) {
      foreach ($webforms as $id => $webform) {
        if (!$webform->access('update', $this->currentUser)
          && !$webform->access('submission_view_any', $this->currentUser)) {
          unset($webforms[$id]);
        }
      }
    }
    $this->loadMetadata($webforms);
    $this->totalNumberOfResults = $webforms
      ? $storage->getTotalNumberOfResults() + array_fill_keys(array_keys($webforms), 0)
      : [];
    return $webforms;
  }

  /**
   * Loads metadata for the forms being displayed or exported.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $webforms
   *   The forms to load metadata for.
   */
  protected function loadMetadata(array $webforms): void {
    $uuids = [];
    foreach ($webforms as $webform) {
      $uuids[] = $webform->uuid();
    }
    $this->metadata = $this->metadataStorage->loadMultiple($uuids);
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The table header.
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $operations = $header['operations'];
    unset($header['operations']);

    $header['metadata_created'] = $this->t('Creation date');
    $header['metadata_created_username'] = $this->t('Created by');
    $header['metadata_changed'] = $this->t('Last modification date');
    $header['metadata_changed_username'] = $this->t('Last edited by');
    $header['metadata_latest_submission'] = $this->t('Latest submission date');
    $header['operations'] = $operations;
    return $header;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The table row.
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    if (!$row) {
      return $row;
    }

    $metadata = $this->metadata[$entity->uuid()] ?? [];
    $timezone = $this->configFactory->get('system.date')->get('timezone.default') ?: 'UTC';
    $operations = $row['operations'];
    unset($row['operations']);

    foreach (['created', 'created_username', 'changed', 'changed_username', 'latest_submission'] as $field) {
      $value = $metadata[$field] ?? NULL;
      if ($value === NULL) {
        $value = $this->t('Unknown');
      }
      elseif (in_array($field, ['created', 'changed', 'latest_submission'], TRUE)) {
        $value = $this->dateFormatter->format($value, 'short', '', $timezone);
      }
      $row['metadata_' . $field]['data'] = ['#plain_text' => (string) $value];
    }

    $row['operations'] = $operations;
    return $row;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response or overview render array.
   */
  public function render() {
    $build = parent::render();
    // Webform can return an autocomplete redirect instead of a render array.
    if (is_array($build)) {
      $url = Url::fromRoute('os2forms_metadata.export');
      $build['metadata_export'] = [
        '#type' => 'link',
        '#title' => $this->t('Download CSV'),
        '#url' => $url,
        '#access' => $url->access($this->currentUser, TRUE),
        '#attributes' => ['class' => ['button']],
        '#weight' => -5,
      ];
      CacheableMetadata::createFromRenderArray($build)
        ->addCacheTags([
          'os2forms_metadata_list',
          'config:system.date',
          'config:core.date_format.short',
        ])
        ->addCacheContexts(['user', 'url.query_args', 'languages:language_interface'])
        ->applyTo($build);
    }
    return $build;
  }

}
