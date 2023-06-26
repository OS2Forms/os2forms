<?php

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\node\Entity\Node;

/**
 * Maestro Select a Content Item.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.
 *     For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when
 *     a task is injected into the queue.
 *
 * @Plugin(
 *   id = "MaestroSelectContent",
 *   task_description = @Translation("The Maestro Engine's Task to Select a Content Item."),
 * )
 */
class MaestroSelectContentTask extends PluginBase implements MaestroEngineTaskInterface {

  use MaestroTaskTrait;

  /**
   * {@inheritDoc}
   */
  public function __construct($configuration = NULL) {
    if (is_array($configuration)) {
      $this->processID = $configuration[0];
      $this->queueID = $configuration[1];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function isInteractive() {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('Create or Select a Content Item');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Create or Select a Content Item');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroSelectContent';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#e2743c';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = [
      '#markup' => $this->t('Select a Content Item.'),
    ];

    $content_type_options = [
      'all' => $this->t('All Content Types'),
    ];
    $content_types_objs = $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($content_types_objs as $content_type_machine_name => $content_type) {
      $content_type_options[$content_type_machine_name] = $content_type_machine_name;
    }

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the Content Type'),
      '#description' => $this->t('Limit the content that can be selected to this content type.'),
      '#required' => TRUE,
      '#options' => $content_type_options,
    ];
    if (isset($task['data']['content_type'])) {
      $form['content_type']['#default_value'] = $task['data']['content_type'];
    }

    $form['unique_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Give this piece of content a unique identifier'),
      '#description' => $this->t('This identifier is stored along with its ID to allow you to recall it when filled in.'),
      '#default_value' => $task['data']['unique_id'] ?? '',
      '#required' => TRUE,
    ];

    $form['redirect_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return Path'),
      '#description' => $this->t('You can specify where your return path should go upon task completion.'),
      '#default_value' => $task['data']['redirect_to'] ?? '/taskconsole',
      '#required' => TRUE,
    ];

    $form['modal'] = [
      '#type' => 'select',
      '#title' => $this->t('Task presentation'),
      '#description' => $this->t('Should this task be shown as a modal or full screen task.'),
      '#default_value' => $task['data']['modal'] ?? 'notmodal',
      '#options' => [
        'modal' => $this->t('Modal'),
        'notmodal' => $this->t('Full Page'),
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {

    $task['data']['modal'] = $form_state->getValue('modal');
    $redirect = $form_state->getValue('redirect_to');
    if (isset($redirect)) {
      $task['data']['redirect_to'] = $redirect;
    }
    else {
      $task['data']['redirect_to'] = '';
    }

    $content_type = $form_state->getValue('content_type');
    if (isset($content_type)) {
      $task['data']['content_type'] = $content_type;
    }
    else {
      $task['data']['content_type'] = '';
    }

    $task['data']['unique_id'] = $form_state->getValue('unique_id');

  }

  /**
   * Part of the ExecutableInterface.
   *
   * Execution of the interactive task does nothing except for
   * setting the run_once flag.
   */
  public function execute() {
    // Need to set the run_once flag here
    // as interactive tasks are executed
    // and completed by the user using the Maestro API.
    $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($this->queueID);
    $queueRecord->set('run_once', 1);
    $queueRecord->save();

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    $form['webform'] = [
      '#id' => 'webform',
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Choose an Existing self-service page'),
        1 => $this->t('Create a New self-service page'),
      ],
      '#default_value' => 0,
      '#title' => $this->t('Create or Select a self-service page'),
      '#required' => TRUE,
    ];

    // Query for all webforms.
    $webforms = [];
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'webform')
      ->execute();
    $webform_nodes = Node::loadMultiple($nids);
    foreach ($webform_nodes as $nid => $webform_node) {
      $webforms[$nid] = $webform_node->getTitle();
    }

    $form['existing_webform'] = [
      '#id' => 'existing_webform',
      '#type' => 'select',
      '#options' => $webforms,
      '#title' => $this->t('Choose an Existing self-service page'),
      '#validated' => TRUE,
      '#prefix' => '<div id="existing-webform-wrapper">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="new_webform"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="new_webform"]' => ['value' => 0],
        ],
      ],
    ];

    $form['queueID'] = [
      '#type' => 'hidden',
      '#title' => 'the queue ID in the event we need it in later processing',
      '#default_value' => $this->queueID,
      '#description' => ('queueID'),
    ];

    // Add all of the entity form's fields into a fieldset
    // create a fieldset to hold all the entity form fields.
    // @todo Rather than simply hiding the entity_form it should be loaded and
    // unloaded via JS. Currently required fields in the entity form, can cause
    // this form to not validate, even when the new entity option is not
    // selected and the entity_form is hidden.
    $form['entity_form'] = [
      '#type' => 'fieldset',
      '#title' => 'Opret Selvbetjeningsside',
      '#states' => [
        'invisible' => [
          ':input[name="new_webform"]' => ['value' => 0],
        ],
        'required' => [
          ':input[name="new_webform"]' => ['value' => 1],
        ],
      ],
    ];

    // Create a form_state for this step.
    $form_state = new FormState();

    // Specify a #parents key on our form definition
    // because that is something the widgets themselves expect.
    $form['#parents'] = [];

    // Load an entity and store on the form state.
    $new_webform = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'webform',
    ]);
    $form_state->set('entity', $new_webform);

    // Load the form display.
    $webform_form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.webform.default');
    $form_state->set('form_display', $webform_form_display);
    // Loop over the form display and add fields to the maestro form.
    foreach ($webform_form_display->getComponents() as $name => $component) {
      // Load the component's configured widget.
      $widget = $webform_form_display->getRenderer($name);
      $items = $new_webform->get($name);
      $form['entity_form'][$name] = $widget->form($items, $form, $form_state);
      $form['entity_form'][$name]['#weight'] = $component['weight'];
      // Make the title field required.
      if ($name == "title") {
        $form['entity_form'][$name]['#required'] = FALSE;
        $form['entity_form'][$name]['widget']['#required'] = FALSE;
        $form['entity_form'][$name]['widget'][0]['#required'] = FALSE;
        $form['entity_form'][$name]['widget'][0]['value']['#required'] = FALSE;
        $form['entity_form'][$name]['#states'] = [
          'required' => [
            ':input[name="new_lineup"]' => ['value' => 1],
          ],
        ];
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
      '#weight' => 1000,
    ];

    // If this is a modal task, we use the ajax completion routines
    // and tell the buttons to use our built in completeForm modal closer.
    if ($modal == 'modal') {
      $form['actions']['submit']['#ajax'] = [
        'callback' => [$parent, 'completeForm'],
        'wrapper' => '',
      ];

    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {

    $queueID = intval($form_state->getValue('maestro_queue_id'));
    $triggeringElement = $form_state->getTriggeringElement();
    $processID = MaestroEngine::getProcessIdFromQueueId($queueID);

    if (strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
      // If existing self-service page is chosen simply assign it to this
      // process.
      // If a new self-service is chosen create that self-service page
      // and assign it to this process.
      if ($form_state->getValue('new_webform')) {
        // Create the new self-service page entity.
        $new_webform = \Drupal::entityTypeManager()->getStorage('node')->create([
          'type' => 'webform',
        ]);
        // Load te form display.
        $webform_form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.webform.default');
        // Extract all of the form display's fields that are in the form_state
        // value and assign to the new self-service page.
        $webform_form_display->extractFormValues($new_webform, $form, $form_state);
        // Save the self-service page.
        $new_webform->save();
        // Assign this self-service page to the maestro variable.
        MaestroEngine::setProcessVariable("new_webform", $new_webform->id(), $processID);
        // Bound this entity to this maestro process.
        $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
        MaestroEngine::createEntityIdentifier($processID, 'node', 'webform', $templateTask['data']['unique_id'], $new_webform->id());
        // Complete this task.
        MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
      }
      else {
        $webform_nid = $form_state->getValue('existing_webform');
        MaestroEngine::setProcessVariable("new_webform", $webform_nid, $processID);
        // Bound this entity to this maestro process.
        $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
        MaestroEngine::createEntityIdentifier($processID, 'node', 'webform', $templateTask['data']['unique_id'], $webform_nid);

        // Complete this task.
        MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
      }
    }
    else {
      // we'll complete the task, but we'll also flag it as TASK_STATUS_CANCEL.
      MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
      MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
    }

    $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
    if (isset($task['data']['redirect_to'])) {
      $response = new TrustedRedirectResponse($task['data']['redirect_to']);
      $form_state->setResponse($response);
    }

  }

  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    if ((array_key_exists('modal', $task['data']) && $task['data']['modal'] == '')  || !array_key_exists('modal', $task['data'])) {
      $validation_failure_tasks[] = [
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => $this->t('The Interactive Task has not been set up properly.  The "Task Presentation" option is missing and thus the engine will be unable to execute this task.'),
      ];
    }

    // This task should have assigned users
    // $task['assigned'] should have data.
    if ((array_key_exists('assigned', $task) && $task['assigned'] == '')  || !array_key_exists('assigned', $task)) {
      $validation_failure_tasks[] = [
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => $this->t('The Interactive Task has not been set up properly.  The Interactive Task requires assignments to actors, roles or other assignment options.'),
      ];
    }

    if ((array_key_exists('unique_id', $task['data']) && $task['data']['unique_id'] == '')  || !array_key_exists('unique_id', $task['data'])) {
      $validation_failure_tasks[] = [
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => $this->t('The Content Type Task has not been set up properly.  The "unique identifier" option is missing and thus the engine will be unable to execute this task.'),
      ];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {

    return ['edit', 'drawlineto', 'removelines', 'remove'];

  }

}
