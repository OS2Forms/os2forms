<?php

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\maestro_webform\Plugin\EngineTasks\MaestroWebformTask;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Maestro Webform Task Plugin for Multiple Submissions.
 *
 * @Plugin(
 *   id = "MaestroWebformInherit",
 *   task_description = @Translation("Maestro Webform task for multiple submissions."),
 * )
 */
class MaestroWebformInheritTask extends MaestroWebformTask {

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
    return $this->t('Webform with Inherited submission');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Webform with Inherited submission');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroWebformInherit';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {

    // We call the parent, as we need to add a field to the inherited form.
    $form = parent::getTaskEditForm($task, $templateMachineName);
    $form['inherit_webform_unique_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inherit Webform from:'),
      '#description' => $this->t('Put the unique identifier of the webform you want to inherit from (start-task=submission'),
      '#default_value' => $task['data']['inherit_webform_unique_id'] ?? '',
      '#required' => TRUE,
    ];
    $form['inherit_webform_create_submission'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create submission'),
      '#description' => $this->t('Create submission'),
      '#default_value' => $task['data']['inherit_webform_create_submission'] ?? FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {

    // Inherit from parent.
    parent::prepareTaskForSave($form, $form_state, $task);
    // Add custom field(s) to the inherited prepareTaskForSave method.
    $task['data']['inherit_webform_unique_id'] = $form_state->getValue('inherit_webform_unique_id');
    $task['data']['inherit_webform_create_submission'] = $form_state->getValue('inherit_webform_create_submission');
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {

    // First, get hold of the interesting previous tasks.
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);

    // Get user input from 'inherit_webform_unique_id'.
    $webformInheritID = $task['data']['inherit_webform_unique_id'];

    // Load its corresponding webform submission.
    $sid = MaestroEngine::getEntityIdentiferByUniqueID($this->processID, $webformInheritID);
    if ($sid) {
      $webform_submission = WebformSubmission::load($sid);
    }
    if (!isset($webform_submission)) {
      \Drupal::logger('os2forms_forloeb')->error(
        "Predecessors must have submissions with webforms attached."
      );
      return FALSE;
    }
    // Copy the fields of the webform submission to the values array.
    foreach ($webform_submission->getData() as $key => $value) {
      if ($value) {
        $field_values[$key] = $value;
      }
    }
    // Now create webform submission, submit and attach to current process.
    $templateTask = MaestroEngine::getTemplateTaskByQueueID($this->queueID);
    $webformMachineName = $templateTask['data']['webform_machine_name'];

    $values = [];
    $values['webform_id'] = $webformMachineName;
    $values['data'] = $field_values;

    $createSubmission = (bool) ($task['data']['inherit_webform_create_submission'] ?? FALSE);
    if ($createSubmission) {
      // Create submission.
      $new_submission = WebformSubmission::create($values);

      // Submit the webform submission.
      $submission = WebformSubmissionForm::submitWebformSubmission($new_submission);

      // WebformSubmissionForm::submitWebformSubmission returns an array
      // if the submission is not valid.
      if (is_array($submission)) {
        \Drupal::logger('os2forms_forloeb')->error(
          "Can't create new submission: " . json_encode($submission)
        );
        \Drupal::messenger()->addError('Webform data is invalid and could not be submitted.');
        return FALSE;
      }

      $taskUniqueSubmissionId = $templateTask['data']['unique_id'];

      // Attach it to the Maestro process.
      $sid = $new_submission->id();
      MaestroEngine::createEntityIdentifier(
        $this->processID, $new_submission->getEntityTypeId(),
        $new_submission->bundle(), $taskUniqueSubmissionId, $sid
      );

      // Important: Apparently the form must be generated after calling
      // MaestroEngine::createEntityIdentifier for this to work.
      $form = parent::getExecutableForm($modal, $parent);
      // Catch os2forms-forloeb access token and pass it further.
      if ($form instanceof RedirectResponse && $token = \Drupal::request()->query->get('os2forms-forloeb-ws-token')) {
        // Check token to previous submission and update it to new one.
        if ($token === $webform_submission->getToken()) {
          $token = $new_submission->getToken();
          $url = Url::fromUserInput($form->getTargetUrl(), ['query' => ['os2forms-forloeb-ws-token' => $token]]);
          $form = new RedirectResponse($url->toString());
        }
      }
    }
    else {
      // Store values in session.
      $values['processID'] = $this->processID;
      $values['queueID'] = $this->queueID;
      $values['webformInheritID'] = $webformInheritID;

      self::setTaskValues($this->queueID, $values);

      $form = parent::getExecutableForm($modal, $parent);
    }

    return $form;
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  public static function webformSubmissionPrepareForm(WebformSubmissionInterface $webformSubmission, string $operation, FormStateInterface $formState): void {
    $request = \Drupal::request();
    $isMaestro = (bool) $request->query->get('maestro', 0);
    $queueID = (int) $request->query->get('queueid', 0);
    if ($isMaestro && $queueID > 0) {
      $values = self::getTaskValues($queueID);
      if (isset($values['data'])) {
        foreach ($values['data'] as $name => $value) {
          $webformSubmission->setElementData($name, $value);
        }
      }
    }
  }

  /**
   * Get task values from session.
   *
   * @param int $queueID
   *   The queue ID.
   *
   * @return array
   *   The task values if any.
   */
  private static function getTaskValues($queueID) {
    $sessionKey = self::formatTaskValuesSessionKey($queueID);
    return \Drupal::request()->getSession()->get($sessionKey);
  }

  /**
   * Set task values in session.
   *
   * @param int $queueID
   *   The queue ID.
   * @param array $values
   *   The values.
   */
  private static function setTaskValues($queueID, array $values) {
    $sessionKey = self::formatTaskValuesSessionKey($queueID);
    \Drupal::request()->getSession()->set($sessionKey, $values);
  }

  /**
   * Format task values session key.
   *
   * @param int $queueID
   *   The queue ID.
   *
   * @return string
   *   The formatted session key.
   */
  private static function formatTaskValuesSessionKey($queueID) {
    return sprintf('os2forms_forloeb_inherited_values_%s', $queueID);
  }

}
