<?php

namespace Drupal\os2forms_webform_list;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformSubmissionListBuilder;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class CustomWebformSubmissionListBuilder extends WebformSubmissionListBuilder {

  /**
   * Build the webform submission entity list.
   *
   * @return array
   *   A renderable array containing the entity list.
   */
  protected function buildEntityList(): array {
    $build = [];

    // Filter form.
    if (empty($this->account)) {
      $build['filter_form'] = $this->buildFilterForm();
    }

    // Customize buttons.
    if ($this->customize) {
      $build['customize'] = $this->buildCustomizeButton();
    }

    // Display info.
    if ($this->total) {
      $build['info'] = $this->buildInfo();
    }

    // Table.
    $build += EntityListBuilder::render();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-results-table';

    // Bulk operations only visible on webform submissions pages.
    $webform_submission_bulk_form = $this->configFactory->get('webform.settings')->get('settings.webform_submission_bulk_form');
    if ($webform_submission_bulk_form
      && !$this->account
      && $this->webform
      && $this->webform->access('submission_update_any')
      && $this->currentUser->hasPermission('access webform submission list bulk operations and actions')) {
      $build['table'] = \Drupal::formBuilder()->getForm('\Drupal\webform\Form\WebformSubmissionBulkForm', $build['table'], $this->webform->access('submission_delete_any'));
    }

    // Must preload libraries required by (modal) dialogs.
    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Add permissions check on operations.
   *
   * @return array
   *   A renderable array containing the entity list.
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    if ($this->currentUser->hasPermission('access webform submission list bulk operations and actions')) {
      return parent::getDefaultOperations($entity);
    }
    else {
      $webform = $entity->getWebform();
      $operations = [];

      if ($entity->access('view')) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'weight' => 20,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform.user.submission'),
        ];
      }

      if ($entity->access('view_any')
        && $this->currentUser->hasPermission('access webform submission log')
        && $webform->hasSubmissionLog()
        && $this->moduleHandler->moduleExists('webform_submission_log')) {
        $operations['log'] = [
          'title' => $this->t('Log'),
          'weight' => 100,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.log'),
        ];
      }

      return $operations;
    }
  }

}

