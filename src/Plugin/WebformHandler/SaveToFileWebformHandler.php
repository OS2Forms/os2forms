<?php

namespace Drupal\os2forms\Plugin\WebformHandler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\BooleanBase;
use Drupal\webform\Plugin\WebformElement\NumericBase;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission save to file handler.
 *
 * @WebformHandler(
 *   id = "save_to_file",
 *   label = @Translation("EXPERIMENTAL: Save to file"),
 *   category = @Translation("External"),
 *   description = @Translation("EXPERIMENTAL: Saves webform submissions to a file."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class SaveToFileWebformHandler extends WebformHandlerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The DrupalKernel instance used in the test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * List of unsupported webform submission properties.
   *
   * The below properties will not being included in a remote post.
   *
   * @var array
   */
  protected $unsupportedProperties = [
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->tokenManager = $container->get('webform.token_manager');
    $instance->messageManager = $container->get('webform.message_manager');
    $instance->elementManager = $container->get('plugin.manager.webform.element');

    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->kernel = $container->get('kernel');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];

    if (!$this->isResultsEnabled()) {
      $settings['updated_path'] = '';
      $settings['deleted_path'] = '';
    }
    if (!$this->isDraftEnabled()) {
      $settings['draft_created_path'] = '';
      $settings['draft_updated_path'] = '';
    }
    if (!$this->isConvertEnabled()) {
      $settings['converted_path'] = '';
    }

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $field_names = array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('webform_submission'));
    $excluded_data = array_combine($field_names, $field_names);
    return [
      'file_type' => 'json',
      'excluded_data' => $excluded_data,
      'custom_data' => '',
      'custom_options' => '',
      'cast' => FALSE,
      'debug' => FALSE,
      // States.
      'completed_path' => '',
      'completed_custom_data' => '',
      'updated_path' => '',
      'updated_custom_data' => '',
      'deleted_path' => '',
      'deleted_custom_data' => '',
      'draft_created_path' => '',
      'draft_created_custom_data' => '',
      'draft_updated_path' => '',
      'draft_updated_custom_data' => '',
      'converted_path' => '',
      'converted_custom_data' => '',
      // Custom error response messages.
      'message' => '',
      'messages' => [],
      // Custom error response redirect URL.
      'error_url' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();

    // States.
    $states = [
      WebformSubmissionInterface::STATE_COMPLETED => [
        'state' => $this->t('completed'),
        'label' => $this->t('Completed'),
        'description' => $this->t('Post data when <b>submission is completed</b>.'),
        'access' => TRUE,
      ],
      WebformSubmissionInterface::STATE_UPDATED => [
        'state' => $this->t('updated'),
        'label' => $this->t('Updated'),
        'description' => $this->t('Post data when <b>submission is updated</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DELETED => [
        'state' => $this->t('deleted'),
        'label' => $this->t('Deleted'),
        'description' => $this->t('Post data when <b>submission is deleted</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT_CREATED => [
        'state' => $this->t('draft created'),
        'label' => $this->t('Draft created'),
        'description' => $this->t('Post data when <b>draft is created.</b>'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT_UPDATED => [
        'state' => $this->t('draft updated'),
        'label' => $this->t('Draft updated'),
        'description' => $this->t('Post data when <b>draft is updated.</b>'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_CONVERTED => [
        'state' => $this->t('converted'),
        'label' => $this->t('Converted'),
        'description' => $this->t('Post data when anonymous <b>submission is converted</b> to authenticated.'),
        'access' => $this->isConvertEnabled(),
      ],
    ];

    $path_pattern = ['public' => 'public?:\/\/'];
    if (\Drupal::service('file_system')->realpath("private://")) {
      $path_pattern['private'] = 'private?:\/\/';
    }

    foreach ($states as $state => $state_item) {
      $state_path = $state . '_path';
      $state_custom_data = $state . '_custom_data';
      $t_args = [
        '@state' => $state_item['state'],
        '@title' => $state_item['label'],
        '@path' => 'public://webforms_submissions',
        '@scheme' => implode(', ', array_keys($path_pattern)),
      ];
      $form[$state] = [
        '#type' => 'details',
        '#open' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#title' => $state_item['label'],
        '#description' => $state_item['description'],
        '#access' => $state_item['access'],
      ];
      $form[$state][$state_path] = [
        '#type' => 'url',
        '#title' => $this->t('@title submissions path', $t_args),
        '#description' => $this->t('Internal path with scheme to folder where an existing webform submissions @state will be saved. (e.g. @path). Allowed scheme: @scheme', $t_args),
        '#required' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#default_value' => $this->configuration[$state_path],
        '#pattern' => '(' . implode('|', $path_pattern) . ').+',
      ];
      $form[$state][$state_custom_data] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('@title custom data', $t_args),
        '#description' => $this->t('Enter custom data that will be included when a webform submission is @state.', $t_args),
        '#states' => [
          'visible' => [':input[name="settings[' . $state_path . ']"]' => ['filled' => TRUE]],
        ],
        '#default_value' => $this->configuration[$state_custom_data],
      ];
    }

    // Additional.
    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    $form['additional']['file_type'] = [
      '#type' => 'select',
      '#title' => $this->t('File type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML webforms. You also have the option to post data in <a href="http://www.json.org/">JSON</a> format.'),
      '#options' => [
        'json' => $this->t('JSON'),
      ],
      '#default_value' => $this->configuration['file_type'],
    ];
    $form['additional']['cast'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cast posted data'),
      '#description' => $this->t('If checked, posted data will be casted to booleans and floats as needed.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['cast'],
    ];
    $form['additional']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all saved files.'),
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['additional']['message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Custom failed save message'),
      '#description' => $this->t('This message is displayed when the file save is faled'),
      '#default_value' => $this->configuration['message'],
    ];
    $form['additional']['error_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom failed save redirect URL'),
      '#description' => $this->t('The URL or path to redirect to when a saving fails.', $t_args),
      '#default_value' => $this->configuration['error_url'],
      '#pattern' => '(https?:\/\/|\/).+',
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    // Submission data.
    $form['submission_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission data'),
    ];
    // Display warning about file uploads.
    if ($this->getWebform()->hasManagedFile()) {
      $form['submission_data']['managed_file_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Upload files will include the file\'s id, name, uri, and data (<a href=":href">Base64</a> encode).', [':href' => 'https://en.wikipedia.org/wiki/Base64']),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_id' => 'webform_node.references',
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }
    $form['submission_data']['excluded_data'] = [
      '#type' => 'webform_excluded_columns',
      '#title' => $this->t('Posted data'),
      '#title_display' => 'invisible',
      '#webform_id' => $webform->id(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['excluded_data'],
    ];

    $this->elementTokenValidate($form);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    $this->saveFile($state, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->saveFile(WebformSubmissionInterface::STATE_DELETED, $webform_submission);
  }

  /**
   * Execute a remote post.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @throws \Exception
   */
  protected function saveFile($state, WebformSubmissionInterface $webform_submission) {
    $file_path = $this->getFilePath($state, $webform_submission);
    if (empty($file_path)) {
      $this->debug($this->t('Save to file successful!'), $state, $file_path);
    }
    $this->messageManager->setWebformSubmission($webform_submission);
    $file_type = $this->configuration['file_type'];
    try {
      $data = $this->getRequestData($state, $webform_submission);
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      $file_dir = $file_system->dirname($file_path);
      if (!file_exists($file_dir)) {
        $file_system->mkdir($file_dir, NULL, TRUE);
      }
      $file_system->saveData($data, $file_path, FileSystemInterface::EXISTS_REPLACE);
    }
    catch (FileWriteException $e) {
      $this->handleError($state, $e->getMessage(), $file_path, $file_type);
      return;
    }

    // If debugging is enabled, display the request and response.
    $this->debug($this->t('Save to file successful!'), $state, $file_path);
  }

  /**
   * Gets file path to save data.
   *
   * @param string $state
   *   String state of webform submission.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission entity object.
   *
   * @return string|null
   *   File path string or null.
   */
  protected function getFilePath($state, WebformSubmissionInterface $webform_submission) {
    $state_path = $this->configuration[$state . '_path'];
    if (empty($state_path)) {
      return NULL;
    }
    $file_type = $this->configuration['file_type'];
    $file_path = $state_path . '/submission-' . $webform_submission->id() . '.' . $file_type;
    $file_path = $this->replaceTokens($file_path, $webform_submission);
    return $file_path;
  }

  /**
   * Get a webform submission's request data.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @throws \Exception
   *
   * @return array
   *   A webform submission converted to an associative array.
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    // Get submission and elements data.
    $data = $webform_submission->toArray(TRUE);

    // Remove unsupported properties from data.
    // These are typically added by other module's like metatag.
    $unsupported_properties = array_combine($this->unsupportedProperties, $this->unsupportedProperties);
    $data = array_diff_key($data, $unsupported_properties);

    // Flatten data and prioritize the element data over the
    // webform submission data.
    $element_data = $data['data'];
    unset($data['data']);
    $data = $element_data + $data;

    // Excluded selected submission data.
    $data = array_diff_key($data, $this->configuration['excluded_data']);

    // Append uploaded file name, uri, and base64 data to data.
    $webform = $this->getWebform();
    foreach ($data as $element_key => $element_value) {
      if (empty($element_value)) {
        continue;
      }

      $element = $webform->getElement($element_key);
      if (!$element) {
        continue;
      }

      $element_plugin = $this->elementManager->getElementInstance($element);

      if ($element_plugin instanceof WebformManagedFileBase) {
        if ($element_plugin->hasMultipleValues($element)) {
          foreach ($element_value as $fid) {
            $data['_' . $element_key][] = $this->getFileData($fid);
          }
        }
        else {
          $data['_' . $element_key] = $this->getFileData($element_value);
          // @deprecated in Webform 8.x-5.0-rc17. Use new format
          // This code will be removed in 8.x-6.x.
          $data += $this->getFileData($element_value, $element_key . '__');
        }
      }
      elseif (!empty($this->configuration['cast'])) {
        $data[$element_key] = $this->castRequestValues($element, $element_plugin, $element_value);
      }
    }

    // Append custom data.
    if (!empty($this->configuration['custom_data'])) {
      $data = Yaml::decode($this->configuration['custom_data']) + $data;
    }

    // Append state custom data.
    if (!empty($this->configuration[$state . '_custom_data'])) {
      $data = Yaml::decode($this->configuration[$state . '_custom_data']) + $data;
    }

    // Replace tokens.
    $data = $this->replaceTokens($data, $webform_submission);

    $file_type = $this->configuration['file_type'];
    switch ($file_type) {
      case 'json':
        $data = Json::encode($data);
    }

    return $data;
  }

  /**
   * Cast request values.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   The element's webform plugin.
   * @param mixed $value
   *   The element's value.
   *
   * @return mixed
   *   The element's values cast to boolean or float when appropriate.
   */
  protected function castRequestValues(array $element, WebformElementInterface $element_plugin, $value) {
    $element_plugin->initialize($element);
    if ($element_plugin->hasMultipleValues($element)) {
      foreach ($value as $index => $item) {
        $value[$index] = $this->castRequestValue($element, $element_plugin, $item);
      }
      return $value;
    }
    else {
      return $this->castRequestValue($element, $element_plugin, $value);
    }
  }

  /**
   * Cast request value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   The element's webform plugin.
   * @param mixed $value
   *   The element's value.
   *
   * @throws \Exception
   *
   * @return mixed
   *   The element's value cast to boolean or float when appropriate.
   */
  protected function castRequestValue(array $element, WebformElementInterface $element_plugin, $value) {
    if ($element_plugin instanceof BooleanBase) {
      return (boolean) $value;
    }
    elseif ($element_plugin instanceof NumericBase) {
      return (float) $value;
    }
    elseif ($element_plugin instanceof WebformCompositeBase) {
      $composite_elements = (isset($element['#element']))
        ? $element['#element']
        : $element_plugin->getCompositeElements();
      foreach ($composite_elements as $key => $composite_element) {
        if (isset($value[$key])) {
          $composite_element_plugin = $this->elementManager->getElementInstance($composite_element);
          $value[$key] = $this->castRequestValue($composite_element, $composite_element_plugin, $value[$key]);
        }
      }
      return $value;
    }
    else {
      return $value;
    }
  }

  /**
   * Get request file data.
   *
   * @param int $fid
   *   A file id.
   * @param string|null $prefix
   *   A prefix to prepended to data.
   *
   * @return array
   *   An associative array containing file data (name, uri, mime, and data).
   */
  protected function getFileData($fid, $prefix = '') {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    if (!$file) {
      return [];
    }

    $data = [];
    $data[$prefix . 'id'] = (int) $file->id();
    $data[$prefix . 'name'] = $file->getFilename();
    $data[$prefix . 'uri'] = $file->getFileUri();
    $data[$prefix . 'mime'] = $file->getMimeType();
    $data[$prefix . 'uuid'] = $file->uuid();
    $data[$prefix . 'data'] = base64_encode(file_get_contents($file->getFileUri()));
    return $data;
  }

  /**
   * Determine if saving of results is enabled.
   *
   * @return bool
   *   TRUE if saving of results is enabled.
   */
  protected function isResultsEnabled() {
    return ($this->getWebform()->getSetting('results_disabled') === FALSE);
  }

  /**
   * Determine if saving of draft is enabled.
   *
   * @return bool
   *   TRUE if saving of draft is enabled.
   */
  protected function isDraftEnabled() {
    return $this->isResultsEnabled() && ($this->getWebform()->getSetting('draft') != WebformInterface::DRAFT_NONE);
  }

  /**
   * Determine if converting anonymous submissions to authenticated is enabled.
   *
   * @return bool
   *   TRUE if converting anonymous submissions to authenticated is enabled.
   */
  protected function isConvertEnabled() {
    return $this->isDraftEnabled() && ($this->getWebform()->getSetting('form_convert_anonymous') === TRUE);
  }

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param string $file_path
   *   The remote URL the request is being posted to.
   * @param string $type
   *   The type of message to be displayed to the end use.
   */
  protected function debug($message, $state, $file_path, $type = 'warning') {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Save to file: @title [@state]', [
        '@title' => $this->label(),
        '@state' => $state,
      ]),
    ];

    // State.
    $build['state'] = [
      '#type' => 'item',
      '#title' => $this->t('Submission state/operation:'),
      '#markup' => $state,
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
        'style' => 'margin: 0',
      ],
    ];

    // Save to file.
    $build['save_to_file'] = ['#markup' => '<hr />'];
    $build['file_path'] = [
      '#type' => 'item',
      '#title' => $this->t('File path'),
      '#markup' => $file_path,
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
        'style' => 'margin: 0',
      ],
    ];
    $build['file_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Request type'),
      '#markup' => $this->configuration['file_type'],
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
        'style' => 'margin: 0',
      ],
    ];

    // Message.
    $build['message'] = ['#markup' => '<hr />'];
    $build['message_message'] = [
      '#type' => 'item',
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      '#title' => $this->t('Message:'),
      '#markup' => $message,
    ];

    $this->messenger()->addMessage(\Drupal::service('renderer')->renderPlain($build), $file_type);
  }

  /**
   * Handle error by logging and display debugging and/or exception message.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param string $message
   *   Message to be displayed.
   * @param string $file_path
   *   The file path.
   * @param string $file_type
   *   The file type.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function handleError($state, $message, $file_path, $file_type) {
    global $base_url, $base_path;

    // If debugging is enabled, display the error message on screen.
    $this->debug($message, $state, $file_path, 'error');

    // Log error message.
    $context = [
      '@form' => $this->getWebform()->label(),
      '@state' => $state,
      '@file_path' => $file_path,
      '@file_type' => $file_type,
      '@message' => $message,
      'webform_submission' => $this->getWebformSubmission(),
      'handler_id' => $this->getHandlerId(),
      'operation' => 'error',
      'link' => $this->getWebform()
        ->toLink($this->t('Edit'), 'handlers')
        ->toString(),
    ];
    $this->getLogger('webform_submission')
      ->error('@form webform submission (@state) save @file_type data to file with path @file_path is failed. @message', $context);

    // Redirect the current request to the error url.
    $error_url = $this->configuration['error_url'];
    if ($error_url && PHP_SAPI !== 'cli') {
      // Convert error path to URL.
      if (strpos($error_url, '/') === 0) {
        $error_url = $base_url . preg_replace('#^' . $base_path . '#', '/', $error_url);
      }
      $response = new TrustedRedirectResponse($error_url);
      // Save the session so things like messages get saved.
      $this->request->getSession()->save();
      $response->prepare($this->request);
      // Make sure to trigger kernel events.
      $this->kernel->terminate($this->request, $response);
      $response->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTokenTreeElement(
    array $token_types = ['webform', 'webform_submission'],
    $description = NULL,
  ) {
    $description = $description ?: $this->t('Use [webform_submission:values:ELEMENT_KEY:raw] to get plain text values.');
    return parent::buildTokenTreeElement($token_types, $description);
  }

}
