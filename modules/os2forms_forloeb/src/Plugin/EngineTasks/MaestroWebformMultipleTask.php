<?php

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro_webform\Plugin\EngineTasks\MaestroWebformTask;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;

/**
 * Maestro Webform Task Plugin for Multiple Submissions.
 *
 * @Plugin(
 *   id = "MaestroWebformMultiple",
 *   task_description = @Translation("Maestro Webform task for multiple submissions."),
 * )
 */
class MaestroWebformMultipleTask extends MaestroWebformTask {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The incoming configuration information from the engine execution.
   *   [0] - is the process ID
   *   [1] - is the queue ID
   *   The processID and queueID properties are defined in the MaestroTaskTrait.
   */
  public function __construct(array $configuration = NULL) {
    if (is_array($configuration)) {
      $this->processID = $configuration[0];
      $this->queueID = $configuration[1];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('Webform task with Multiple Submissions');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Webform task with Multiple Submissions');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroWebformMultiple';
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    // If this is used properly, there's no submission associated with
    // the current task. We will gather data from its predecessor tasks
    // (collected by an AND task) and create a submission using their fields.
    //
    // First, get hold of the interesting previous tasks.
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);

    $pointers = MaestroEngine::getTaskPointersFromTemplate(
      $templateMachineName, $taskMachineName
    );
    // Now, there can only be one task preceding this, the AND
    // task collecting the submissions. Get the predecessors of the AND task.
    $pointers = MaestroEngine::getTaskPointersFromTemplate(
      $templateMachineName, $pointers[0]
    );
    // Now, we query the queue to find the actual tasks (Maestro queue IDs)
    // pointing to the AND task.
    $query = \Drupal::entityQuery('maestro_queue');
    $andMainConditions = $query->andConditionGroup()
      ->condition('process_id', $this->processID);
    $orConditionGroup = $query->orConditionGroup();
    foreach ($pointers as $taskID) {
      $orConditionGroup->condition('task_id', $taskID);
    }
    $andMainConditions->condition($orConditionGroup);
    $query->condition($andMainConditions);
    $queueIDs = $query->execute();

    // This array will hold the key => value pairs for the fields
    // to be copied to the final form.
    $field_values = [];

    foreach ($queueIDs as $queueID) {
      // Load the Maestro task with ID $pid.
      $currentTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
      // Load its corresponding webform submission.
      $taskUniqueSubmissionId = $currentTask['data']['unique_id'];
      $webformMachineName = $currentTask['data']['webform_machine_name'];
      $sid = MaestroEngine::getEntityIdentiferByUniqueID($this->processID, $taskUniqueSubmissionId);
      if ($sid) {
        $webform_submission = WebformSubmission::load($sid);
      }
      if (!$webform_submission) {
        \Drupal::logger('os2forms_forloeb')->error(
          "Predecessors MUSt have submissions with webforms attached."
        );
      }
      // Copy the fields of the webform submission to the values array.
      foreach ($webform_submission->getData() as $key => $value) {
        if ($value) {
          $field_values[$taskUniqueSubmissionId . '_' . $key] = $value;
        }
      }
    }

    // Now create webform submission, submit and attach to current process.
    $templateTask = MaestroEngine::getTemplateTaskByQueueID($this->queueID);
    $taskUniqueSubmissionId = $templateTask['data']['unique_id'];
    $webformMachineName = $templateTask['data']['webform_machine_name'];

    $values = [];
    $values['webform_id'] = $webformMachineName;
    $values['data'] = $field_values;

    // Create submission.
    $new_submission = WebformSubmission::create($values);

    $errors = WebformSubmissionForm::validateWebformSubmission($webform_submission);

    if (!empty($errors)) {
      \Drupal::logger('os2forms_forloeb')->error(
        "Can't create new submission: " . json_encode($errors)
      );
    }
    // Submit it.
    $new_submission = WebformSubmissionForm::submitWebformSubmission($new_submission);

    // Attach it to the Maestro process.
    $sid = $new_submission->id();
    MaestroEngine::createEntityIdentifier(
      $this->processID, $new_submission->getEntityTypeId(),
      $new_submission->bundle(), $taskUniqueSubmissionId, $sid
    );

    return parent::getExecutableForm($modal, $parent);
  }

}
