<?php

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro_webform\Plugin\EngineTasks\MaestroWebformTask;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Maestro Webform Task Plugin for Multiple Submissions.
 *
 * @Plugin(
 *   id = "MaestroWebformInherit",
 *   task_description = @Translation("Maestro Webform task for multiple submissions."),
 * )
 */
class MaestroWebformInheritTask extends MaestroWebformTask {
  public const INHERIT_WEBFORM_UNIQUE_ID = 'inherit_webform_unique_id';

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
    // Get all webform tasks in template excluding the current task.
    $template = MaestroEngine::getTemplate($templateMachineName);
    $webformTasks = array_filter(
      $template->tasks,
      static fn(array $t) => $t['id'] !== $task['id'] && self::isWebformTask($t)
    );

    // We call the parent, as we need to add a field to the inherited form.
    $form = parent::getTaskEditForm($task, $templateMachineName);
    $form[self::INHERIT_WEBFORM_UNIQUE_ID] = [
      '#type' => 'select',
      '#options' => ['submission' => $this->t('Start')]
      + array_map(
          static fn(array $task) => sprintf('%s (%s)', $task['label'], $task['id']),
          $webformTasks
      ),
      '#title' => $this->t('Inherit Webform from:'),
      '#description' => $this->t('Put the unique identifier of the webform you want to inherit from (start-task=submission'),
      '#default_value' => $task['data'][self::INHERIT_WEBFORM_UNIQUE_ID] ?? '',
      '#required' => TRUE,
      '#empty_option' => $this->t('Select task'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentsAndNotificationsForm(array $task, $templateMachineName) {
    $form = parent::getAssignmentsAndNotificationsForm($task, $templateMachineName);

    // @todo Find task by unique_id = $task['data']['inherit_webform_unique_id'] and point to webform.
    $anonymousNotificationMessage = $this->t('Add a Meastro notification handler to the webform for the task selected under %inherit_webform_from', [
      '%inherit_webform_from' => $this->t('Inherit Webform from:'),
    ]);

    WebformArrayHelper::insertBefore(
      $form['edit_task_notifications'], 'token_tree',
      'anonymous_notification_message',
      [
        '#theme' => 'status_messages',
        '#message_list' => [
          'status' => [$anonymousNotificationMessage],
        ],
      ]
    );

    return $form;
  }

  /**
   * Deside if a task is a webform task.
   */
  public static function isWebformTask(array $task): bool {
    return in_array($task['tasktype'] ?? NULL, [
      'MaestroWebform',
      'MaestroWebformInherit',
    ], TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {

    // Inherit from parent.
    parent::prepareTaskForSave($form, $form_state, $task);
    // Add custom field(s) to the inherited prepareTaskForSave method.
    $task['data'][self::INHERIT_WEBFORM_UNIQUE_ID] = $form_state->getValue(self::INHERIT_WEBFORM_UNIQUE_ID);
  }

  /**
   * Implements hook_webform_submission_form_alter().
   */
  public static function webformSubmissionFormAlter(array &$form, FormStateInterface $formState, string $formId) {
    // @todo Clean up and align with MaestroHelper::maestroZeroUserNotification().
    if ($queueID = self::getQueueIdFromRequest()) {
      $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
      if (self::isWebformTask($templateTask)) {
        if ($inheritWebformUniqueId = ($templateTask['data'][self::INHERIT_WEBFORM_UNIQUE_ID] ?? NULL)) {
          $processID = MaestroEngine::getProcessIdFromQueueId($queueID);
          $entityIdentifier = MaestroEngine::getAllEntityIdentifiersForProcess($processID)[$inheritWebformUniqueId] ?? NULL;
          if ('webform_submission' === ($entityIdentifier['entity_type'] ?? NULL)) {
            $submission = WebformSubmission::load($entityIdentifier['entity_id']);
            $data = $submission->getData();
            foreach ($data as $key => $value) {
              if (isset($form['elements'][$key])) {
                $form['elements'][$key]['#default_value'] = $value;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Get Maestro queue ID from request.
   */
  public static function getQueueIdFromRequest(): ?int {
    $queueID = NULL;
    $request = \Drupal::request();
    if ($sitewideToken = \Drupal::service('config.factory')->get('maestro.settings')->get('maestro_sitewide_token')) {
      $token = $request->query->get($sitewideToken);
      if (is_string($token)) {
        $queueID = MaestroEngine::getQueueIdFromToken($token);
      }
    }
    if (empty($queueID)) {
      $queueID = $request->query->get('queueid');
    }

    return (int) $queueID ?: NULL;
  }

}
