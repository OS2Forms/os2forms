<?php

namespace Drupal\os2forms_metadata;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;
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
   * The metadata field selected for sorting, or an empty string.
   */
  protected string $metadataSortField = '';

  /**
   * The selected metadata sort direction.
   */
  protected string $metadataSortDirection = 'asc';

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
    if (!$this->metadataSortField) {
      $this->loadMetadata($webforms);
    }
    return $webforms;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string>
   *   The form IDs for the current page.
   */
  protected function getEntityIds() {
    $header = $this->buildHeader();
    $order = TableSort::getOrder($header, $this->request);
    $field = (string) $order['sql'];
    $this->metadataSortField = '';
    if (!isset($header[$field]) || !str_starts_with($field, 'metadata_')) {
      return parent::getEntityIds();
    }

    $this->metadataSortField = substr($field, strlen('metadata_'));
    $this->metadataSortDirection = TableSort::getSort($header, $this->request);

    return $this->getMetadataEntityIds();
  }

  /**
   * Sorts matching forms by metadata and selects the requested page.
   *
   * @return array<string>
   *   The form IDs for the current page.
   */
  protected function getMetadataEntityIds(): array {
    $ids = $this->getQuery($this->keys, $this->category, $this->state)->execute();
    $webforms = $this->getStorage()->loadMultiple($ids);
    $this->loadMetadata($webforms);
    uasort($webforms, [$this, 'compareMetadata']);
    $ids = array_keys($webforms);

    // Sort all matching forms before selecting the requested page.
    $limit = $this->getLimit();
    if ($limit) {
      $pager = \Drupal::service('pager.manager')->createPager(count($ids), $limit);
      $ids = array_slice($ids, $pager->getCurrentPage() * $limit, $limit);
    }

    /** @var \Drupal\webform\WebformEntityStorageInterface $storage */
    $storage = $this->getStorage();
    $this->totalNumberOfResults = $ids
      ? $storage->getTotalNumberOfResults() + array_fill_keys($ids, 0)
      : [];
    return $ids;
  }

  /**
   * Compares metadata values, placing unknown values last in either direction.
   */
  protected function compareMetadata(EntityInterface $first, EntityInterface $second): int {
    $firstValue = $this->metadata[$first->uuid()][$this->metadataSortField] ?? NULL;
    $secondValue = $this->metadata[$second->uuid()][$this->metadataSortField] ?? NULL;
    $comparison = 0;
    if ($firstValue !== NULL && $secondValue !== NULL) {
      if (in_array($this->metadataSortField, ['created_username', 'changed_username'], TRUE)) {
        $comparison = strnatcasecmp($firstValue, $secondValue);
      }
      else {
        $comparison = (int) $firstValue <=> (int) $secondValue;
      }
      if ($this->metadataSortDirection === 'desc') {
        $comparison = -$comparison;
      }
    }
    elseif ($firstValue !== $secondValue) {
      return $firstValue === NULL ? 1 : -1;
    }

    // Keep ties stable when paging or changing the primary sort direction.
    return $comparison
      ?: strnatcasecmp((string) $first->label(), (string) $second->label())
      ?: strcmp((string) $first->id(), (string) $second->id());
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

    $header['metadata_created'] = [
      'data' => $this->t('Creation date'),
      'field' => 'metadata_created',
      'initial_click_sort' => 'desc',
    ];
    $header['metadata_created_username'] = [
      'data' => $this->t('Created by'),
      'field' => 'metadata_created_username',
      'initial_click_sort' => 'asc',
    ];
    $header['metadata_changed'] = [
      'data' => $this->t('Last modification date'),
      'field' => 'metadata_changed',
      'initial_click_sort' => 'desc',
    ];
    $header['metadata_changed_username'] = [
      'data' => $this->t('Last edited by'),
      'field' => 'metadata_changed_username',
      'initial_click_sort' => 'asc',
    ];
    $header['metadata_latest_submission'] = [
      'data' => $this->t('Latest submission date'),
      'field' => 'metadata_latest_submission',
      'initial_click_sort' => 'desc',
    ];

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
      $exportLink = [
        '#type' => 'link',
        '#title' => $this->t('Download CSV'),
        '#url' => $url,
        '#access' => $url->access($this->currentUser, TRUE),
        '#attributes' => ['class' => ['button']],
        '#weight' => 10,
      ];

      if (isset($build['table']['operations']['apply_above'])) {
        $build['table']['operations']['metadata_export'] = $exportLink;
      }

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
