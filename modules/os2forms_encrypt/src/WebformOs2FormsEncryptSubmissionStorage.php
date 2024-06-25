<?php

namespace Drupal\os2forms_encrypt;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\AccountInterface;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_encrypt\WebformEncryptSubmissionStorage;
use Drupal\webform_revisions\Controller\WebformRevisionsController;

/**
 * This class extension WebformEncryptSubmissionStorage.
 *
 * This is to encrypt just the data sent to database and check if webform
 * revisions are enabled.
 *
 * This mostly runs the same code (copied here as multiple
 * inherits is not a thing in PHP), with minor tweaks.
 */
class WebformOs2FormsEncryptSubmissionStorage extends WebformEncryptSubmissionStorage {

  /**
   * {@inheritdoc}
   *
   * Overwritten to add if webform_revisions module exists.
   *
   * @see Drupal\webform\WebformSubmissionStorage::getColumns
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    if (!\Drupal::moduleHandler()->moduleExists('webform_revisions')) {
      return parent::getColumns($webform, $source_entity, $account, $include_elements);
    }
    else {
      $view_any = $webform && $webform->access('submission_view_any');

      $columns = [];

      // Serial number.
      $columns['serial'] = [
        'title' => $this->t('#'),
      ];

      // Submission ID.
      $columns['sid'] = [
        'title' => $this->t('SID'),
      ];

      // Submission label.
      $columns['label'] = [
        'title' => $this->t('Submission title'),
        'sort' => FALSE,
      ];

      // UUID.
      $columns['uuid'] = [
        'title' => $this->t('UUID'),
      ];

      // Draft.
      $columns['in_draft'] = [
        'title' => $this->t('In draft'),
      ];

      if (empty($account)) {
        // Sticky (Starred/Unstarred).
        $columns['sticky'] = [
          'title' => $this->t('Starred'),
        ];

        // Locked.
        $columns['locked'] = [
          'title' => $this->t('Locked'),
        ];

        // Notes.
        $columns['notes'] = [
          'title' => $this->t('Notes'),
        ];
      }

      // Created.
      $columns['created'] = [
        'title' => $this->t('Created'),
      ];

      // Completed.
      $columns['completed'] = [
        'title' => $this->t('Completed'),
      ];

      // Changed.
      $columns['changed'] = [
        'title' => $this->t('Changed'),
      ];

      // Source entity.
      if ($view_any && empty($source_entity)) {
        $columns['entity'] = [
          'title' => $this->t('Submitted to'),
          'sort' => FALSE,
        ];
      }

      // Submitted by.
      if (empty($account)) {
        $columns['uid'] = [
          'title' => $this->t('User'),
        ];
      }

      // Submission language.
      if ($view_any && \Drupal::moduleHandler()->moduleExists('language')) {
        $columns['langcode'] = [
          'title' => $this->t('Language'),
        ];
      }

      // Remote address.
      $columns['remote_addr'] = [
        'title' => $this->t('IP address'),
      ];

      // Webform and source entity for entity.webform_submission.collection.
      // @see /admin/structure/webform/submissions/manage
      if (empty($webform) && empty($source_entity)) {
        $columns['webform_id'] = [
          'title' => $this->t('Webform'),
        ];
        $columns['entity'] = [
          'title' => $this->t('Submitted to'),
          'sort' => FALSE,
        ];
      }

      // Webform elements.
      if ($webform && $include_elements) {
        /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
        $element_manager = \Drupal::service('plugin.manager.webform.element');
        $content_entity_id = $webform->getContentEntityID();
        $revision_ids = $this->database->query(
          'SELECT revision FROM {config_entity_revisions_revision} WHERE id = :id',
          [':id' => $content_entity_id]
        )->fetchCol();
        if (!$revision_ids) {
          return parent::getColumns($webform, $source_entity, $account, $include_elements);
        }

        foreach ($revision_ids as $revision_id) {
          $revisionController = WebformRevisionsController::create(\Drupal::getContainer());
          $webform = $revisionController->loadConfigEntityRevision($revision_id, $webform->id());
          $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');
          foreach ($elements as $element) {
            /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
            $element_plugin = $element_manager->createInstance($element['#type']);
            // Replace tokens which can be used in an element's #title.
            $element_plugin->replaceTokens($element, $webform);
            $columns += $element_plugin->getTableColumn($element);
          }
        }
      }

      // Operations.
      $columns['operations'] = [
        'title' => $this->t('Operations'),
        'sort' => FALSE,
      ];

      // Add name and format to all columns.
      foreach ($columns as $name => &$column) {
        $column['name'] = $name;
        $column['format'] = 'value';
      }

      return $columns;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Overwritten to only encrypt data send to database.
   *
   * @see Drupal\webform\WebformSubmissionStorage::saveData
   */
  public function saveData(WebformSubmissionInterface $webform_submission, $delete_first = TRUE) {
    // Get submission data rows.
    $data_original = $webform_submission->getData();

    $webform = $webform_submission->getWebform();

    $encrypted_data = $this->encryptElements($data_original, $webform);

    $webform_submission->setData($encrypted_data);

    $webform_id = $webform_submission->getWebform()->id();
    $sid = $webform_submission->id();

    $elements = $webform_submission->getWebform()->getElementsInitializedFlattenedAndHasValue();
    $computed_elements = $webform_submission->getWebform()->getElementsComputed();

    $rows = [];
    foreach ($encrypted_data as $name => $item) {
      $element = $elements[$name] ?? ['#webform_multiple' => FALSE, '#webform_composite' => FALSE];

      // Check if this is a computed element which is not
      // stored in the database.
      $is_computed_element = (isset($computed_elements[$name])) ? TRUE : FALSE;
      if ($is_computed_element && empty($element['#store'])) {
        continue;
      }

      if ($element['#webform_composite']) {
        if (is_array($item)) {
          $composite_items = (empty($element['#webform_multiple'])) ? [$item] : $item;
          foreach ($composite_items as $delta => $composite_item) {
            foreach ($composite_item as $property => $value) {
              $rows[] = [
                'webform_id' => $webform_id,
                'sid' => $sid,
                'name' => $name,
                'property' => $property,
                'delta' => $delta,
                'value' => (string) $value,
              ];
            }
          }
        }
      }
      elseif ($element['#webform_multiple']) {
        if (is_array($item)) {
          foreach ($item as $delta => $value) {
            $rows[] = [
              'webform_id' => $webform_id,
              'sid' => $sid,
              'name' => $name,
              'property' => '',
              'delta' => $delta,
              'value' => (string) $value,
            ];
          }
        }
      }
      else {
        $rows[] = [
          'webform_id' => $webform_id,
          'sid' => $sid,
          'name' => $name,
          'property' => '',
          'delta' => 0,
          'value' => (string) $item,
        ];
      }
    }

    if ($delete_first) {
      // Delete existing submission data rows.
      $this->database->delete('webform_submission_data')
        ->condition('sid', $sid)
        ->execute();
    }

    // Insert new submission data rows.
    $query = $this->database
      ->insert('webform_submission_data')
      ->fields(['webform_id', 'sid', 'name', 'property', 'delta', 'value']);
    foreach ($rows as $row) {
      $query->values($row);
    }
    $query->execute();

    $webform_submission->setData($data_original);
  }

  /**
   * {@inheritdoc}
   *
   * Overwritten to avoid webform_submission->getData() before decryption,
   * as this may cause issues with computed elements, e.g. computed twig.
   *
   * @see Drupal\webform_encrypt\WebformEncryptSubmissionStorage::loadData
   * @see Drupal\webform\WebformSubmissionStorage::loadData
   */
  protected function loadData(array &$webform_submissions) {

    // Load webform submission data.
    if ($sids = array_keys($webform_submissions)) {
      $submissions_data = [];

      // Initialize all multiple value elements to make sure a value is defined.
      $webform_default_data = [];
      foreach ($webform_submissions as $sid => $webform_submission) {
        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = $webform_submissions[$sid]->getWebform();
        $webform_id = $webform->id();
        if (!isset($webform_default_data[$webform_id])) {
          $webform_default_data[$webform_id] = [];
          $elements = ($webform) ? $webform->getElementsInitializedFlattenedAndHasValue() : [];
          foreach ($elements as $element_key => $element) {
            if (!empty($element['#webform_multiple'])) {
              $webform_default_data[$webform_id][$element_key] = [];
            }
          }
        }
        $submissions_data[$sid] = $webform_default_data[$webform_id];
      }

      /** @var \Drupal\Core\Database\StatementInterface $result */
      $result = $this->database->select('webform_submission_data', 'sd')
        ->fields('sd', ['webform_id', 'sid', 'name', 'property', 'delta', 'value'])
        ->condition('sd.sid', $sids, 'IN')
        ->orderBy('sd.sid', 'ASC')
        ->orderBy('sd.name', 'ASC')
        ->orderBy('sd.property', 'ASC')
        ->orderBy('sd.delta', 'ASC')
        ->execute();
      while ($record = $result->fetchAssoc()) {
        $sid = $record['sid'];
        $name = $record['name'];

        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = $webform_submissions[$sid]->getWebform();
        $elements = ($webform) ? $webform->getElementsInitializedFlattenedAndHasValue() : [];
        $element = $elements[$name] ?? ['#webform_multiple' => FALSE, '#webform_composite' => FALSE];

        if ($element['#webform_composite']) {
          if ($element['#webform_multiple']) {
            $submissions_data[$sid][$name][$record['delta']][$record['property']] = $record['value'];
          }
          else {
            $submissions_data[$sid][$name][$record['property']] = $record['value'];
          }
        }
        elseif ($element['#webform_multiple']) {
          $submissions_data[$sid][$name][$record['delta']] = $record['value'];
        }
        else {
          $submissions_data[$sid][$name] = $record['value'];
        }
      }


      foreach ($submissions_data as $sid => $submission_data) {
        $this->decryptChildren($submission_data);
        $webform_submissions[$sid]->setData($submission_data);
        $webform_submissions[$sid]->setOriginalData($submission_data);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Overwritten to avoid encrypting null.
   *
   * @see Drupal\webform_encrypt\WebformEncryptSubmissionStorage::encryptElements
   */
  public function encryptElements(array $data, WebformInterface $webform) {
    // Load the configuration.
    $config = $webform->getThirdPartySetting('webform_encrypt', 'element');

    foreach ($data as $element_name => $value) {
      $encryption_profile = isset($config[$element_name]) ? EncryptionProfile::load($config[$element_name]['encrypt_profile']) : FALSE;
      // If the value is an array and we have a encryption profile.
      if ($encryption_profile) {
        if (is_array($value)) {
          $this->encryptChildren($data[$element_name], $encryption_profile);
        }
        else {
          if (is_null($value)) {
            $data[$element_name] = $value;
          } else {
            $encrypted_value = $this->encrypt($value, $encryption_profile);
            // Save the encrypted data value.
            $data[$element_name] = $encrypted_value;
          }
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   *
   * Overwritten to avoid encryption, see saveData.
   *
   * @see Drupal\webform_encrypt\WebformEncryptSubmissionStorage::doPreSave
   * @see Drupal\webform\WebformSubmissionStorage::doPreSave
   * @see Drupal\Core\Entity\ContentEntityStorageBase::doPreSave
   * @see Drupal\Core\Entity\EntityStorageBase::doPreSave
   *
   */
  protected function doPreSave(EntityInterface $entity)
  {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    // From ContentEntityStorageBase.php.

    // Sync the changes made in the fields array to the internal values array.
    $entity->updateOriginalValues();

    if ($entity->getEntityType()->isRevisionable() && !$entity->isNew() && empty($entity->getLoadedRevisionId())) {
      // Update the loaded revision id for rare special cases when no loaded
      // revision is given when updating an existing entity. This for example
      // happens when calling save() in hook_entity_insert().
      $entity->updateLoadedRevisionId();
    }

    $id = $entity->id();

    // Track the original ID.
    if ($entity->getOriginalId() !== NULL) {
      $id = $entity->getOriginalId();
    }

    // Track if this entity exists already.
    $id_exists = $this->has($id, $entity);

    // A new entity should not already exist.
    if ($id_exists && $entity->isNew()) {
      throw new EntityStorageException("'{$this->entityTypeId}' entity with ID '$id' already exists.");
    }

    // Load the original entity, if any.
    if ($id_exists && !isset($entity->original)) {
      $entity->original = $this->loadUnchanged($id);
    }

    // Allow code to run before saving.
    $entity->preSave($this);
    $this->invokeHook('presave', $entity);

    if (!$entity->isNew()) {
      // If the ID changed then original can't be loaded, throw an exception
      // in that case.
      if (empty($entity->original) || $entity->id() != $entity->original->id()) {
        throw new EntityStorageException("Update existing '{$this->entityTypeId}' entity while changing the ID is not supported.");
      }
      // Do not allow changing the revision ID when resaving the current
      // revision.
      if (!$entity->isNewRevision() && $entity->getRevisionId() != $entity->getLoadedRevisionId()) {
        throw new EntityStorageException("Update existing '{$this->entityTypeId}' entity revision while changing the revision ID is not supported.");
      }
    }

    $this->invokeWebformElements('preSave', $entity);
    $this->invokeWebformHandlers('preSave', $entity);

    return $id;
  }

  /**
   * {@inheritdoc}
   *
   * Overwritten to avoid encrypting null.
   *
   * @see Drupal\webform_encrypt\WebformEncryptSubmissionStorage::encryptChildren
   */
  public function encryptChildren(array &$data, EncryptionProfileInterface $encryption_profile) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $this->encryptChildren($data[$key], $encryption_profile);
      }
      elseif (is_null($value)) {
        $data[$key] = $value;
      }
      else {
        $encrypted_value = $this->encrypt($value, $encryption_profile);
        $data[$key] = $encrypted_value;
      }
    }
  }

}
