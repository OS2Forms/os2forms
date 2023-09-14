<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\os2forms_forloeb\MaestroHelper;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maestro notification handler.
 *
 * @WebformHandler(
 *   id = "os2forms_forloeb_maestro_notification",
 *   label = @Translation("Maestro notification"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Sends Meastro notification to known anonymous user."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class MaestroNotificationHandler extends WebformHandlerBase {
  public const NOTIFICATION = 'notification';

  public const TYPE = 'type';
  public const SENDER_LABEL = 'sender_label';
  public const NOTIFICATION_ENABLE = 'notification_enable';
  public const NOTIFICATION_RECIPIENT = 'notification_recipient';
  public const NOTIFICATION_SUBJECT = 'notification_subject';
  public const NOTIFICATION_CONTENT = 'notification_content';
  public const NOTIFICATION_ACTION_LABEL = 'notification_action_label';
  public const RECIPIENT_ELEMENT = 'recipient_element';

  private const TOKEN_MAESTRO_TASK_URL = '[maestro:task-url]';

  /**
   * Maximum length of sender label.
   */
  private const SENDER_LABEL_MAX_LENGTH = 64;

  /**
   * Maximum length of header label.
   */
  private const NOTIFICATION_SUBJECT_MAX_LENGTH = 128;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->loggerFactory = $container->get('logger.factory');
    $instance->configFactory = $container->get('config.factory');
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->conditionsValidator = $container->get('webform_submission.conditions_validator');

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'info' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup' => $this->t('Sends notification (@enabled_notification_types) when triggered by Maestro. The notification will be sent to the person identified by the value of the %element element.', [
          '@enabled_notification_types' => implode(', ', $this->getEnabledNotifications()),
          '%element' => $this->configuration[self::NOTIFICATION][self::RECIPIENT_ELEMENT] ?? NULL,
        ]),
      ],
      'preview' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ]
      + Link::createFromRoute(
          $this->t('Preview notifications'),
          'os2forms_forloeb.meastro_notification.preview', [
            'webform' => $this->getWebform()->id(),
            'handler' => $this->getHandlerId(),
            'content_type' => 'email',
          ]
      )->toRenderable(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form[self::NOTIFICATION] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification'),
    ];

    $availableElements = $this->getRecipientElements();
    $form[self::NOTIFICATION][static::RECIPIENT_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('Element that contains the recipient identifier (email, CPR or CVR) of the notification'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::NOTIFICATION][self::RECIPIENT_ELEMENT] ?? NULL,
      '#options' => $availableElements,
    ];

    $form[self::NOTIFICATION][self::SENDER_LABEL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender label'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::NOTIFICATION][self::SENDER_LABEL] ?? NULL,
      '#maxlength' => self::SENDER_LABEL_MAX_LENGTH,
    ];

    foreach ([
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT => $this->t('Assignment'),
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_REMINDER => $this->t('Reminder'),
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION => $this->t('Escalation'),
    ] as $notificationType => $label) {
      $states = static function (bool $required = TRUE) use ($notificationType): array {
        $states = [
          'visible' => [
            ':input[name="settings[notification][' . $notificationType . '][notification_enable]"]' => ['checked' => TRUE],
          ],
        ];

        if ($required) {
          $states['required'] = [
            ':input[name="settings[notification][' . $notificationType . '][notification_enable]"]' => ['checked' => TRUE],
          ];
        }

        return $states;
      };

      $form[self::NOTIFICATION][$notificationType] = [
        '#type' => 'fieldset',
        '#title' => $label,
      ];

      $form[self::NOTIFICATION][$notificationType][self::NOTIFICATION_ENABLE] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @type notification', ['@type' => $label]),
        '#default_value' => $this->configuration[self::NOTIFICATION][$notificationType][self::NOTIFICATION_ENABLE] ?? ($notificationType === MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT),
      ];

      if ($notificationType === MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION) {
        $form[self::NOTIFICATION][$notificationType][self::NOTIFICATION_RECIPIENT] = [
          '#type' => 'email',
          '#title' => $this->t('@type recipient', ['@type' => $label]),
          '#default_value' => $this->configuration[self::NOTIFICATION][$notificationType][self::NOTIFICATION_RECIPIENT] ?? NULL,
          '#states' => $states(),
        ];
      }

      $form[self::NOTIFICATION][$notificationType][self::NOTIFICATION_SUBJECT] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $this->configuration[self::NOTIFICATION][$notificationType][self::NOTIFICATION_SUBJECT] ?? NULL,
        '#maxlength' => self::NOTIFICATION_SUBJECT_MAX_LENGTH,
        '#states' => $states(),
      ];

      $content = $this->configuration[self::NOTIFICATION][$notificationType][self::NOTIFICATION_CONTENT] ?? NULL;
      if (isset($content['value'])) {
        $content = $content['value'];
      }
      $form[self::NOTIFICATION][$notificationType][self::NOTIFICATION_CONTENT] = [
        '#type' => 'text_format',
        '#format' => 'restricted_html',
        '#title' => $this->t('Message'),
        '#default_value' => $content ?? self::TOKEN_MAESTRO_TASK_URL,
        '#description' => $this->t('The actual notification content. Must contain the <code>@token_maestro_task_url</code> token which is the URL to the Maestro task.',
        [
          '@token_maestro_task_url' => self::TOKEN_MAESTRO_TASK_URL,
        ]),
        '#states' => $states(),
      ];

      $form[self::NOTIFICATION][$notificationType][self::NOTIFICATION_ACTION_LABEL] = [
        '#type' => 'textfield',
        '#title' => $this->t('Action label'),
        '#default_value' => $this->configuration[self::NOTIFICATION][$notificationType][self::NOTIFICATION_ACTION_LABEL] ?? NULL,
        '#description' => $this->t('Label of the action in digital post'),
        '#states' => $states(required: FALSE),
      ];
    }

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::validateConfigurationForm($form, $formState);

    foreach ([
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT,
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_REMINDER,
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION,
    ] as $notificationType) {
      $key = [self::NOTIFICATION, $notificationType, self::NOTIFICATION_ENABLE];
      $enabled = $formState->getValue($key);
      if (!$enabled) {
        break;
      }
      $key = [self::NOTIFICATION, $notificationType, self::NOTIFICATION_CONTENT];
      $content = $formState->getValue($key);
      if (isset($content['value'])) {
        $content = $content['value'];
      }
      if (!str_contains($content, self::TOKEN_MAESTRO_TASK_URL)) {
        $formState->setErrorByName(
          implode('][', [self::NOTIFICATION, self::NOTIFICATION_CONTENT]),
          $this->t('The notification content must contain the <code>@token_maestro_task_url</code> token', [
            '@token_maestro_task_url' => self::TOKEN_MAESTRO_TASK_URL,
          ])
        );
      }
    }
  }

  /**
   * Get recipient elements.
   */
  private function getRecipientElements(): array {
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    $elementTypes = [
      'email',
      'textfield',
      'cpr_element',
      'cpr_value_element',
      'cvr_element',
      'cvr_value_element',
      'os2forms_person_lookup',
    ];
    $elements = array_filter(
      $elements,
      static function (array $element) use ($elementTypes) {
        return in_array($element['#type'], $elementTypes, TRUE);
      }
    );

    return array_map(static function (array $element) {
      return $element['#title'];
    }, $elements);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    $this->configuration[self::NOTIFICATION] = $formState->getValue(self::NOTIFICATION);
  }

  /**
   * Get all notification types.
   */
  public function getEnabledNotifications(): array {
    $enabledNotificationTypes = [];

    foreach ([
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT,
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_REMINDER,
      MaestroHelper::OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION,
    ] as $notificationType) {
      if ($this->configuration[self::NOTIFICATION][$notificationType][self::NOTIFICATION_ENABLE] ?? FALSE) {
        $enabledNotificationTypes[$notificationType] = $notificationType;
      }
    }

    return $enabledNotificationTypes;
  }

  /**
   * Check if a notification type is enabled.
   */
  public function isNotificationEnabled(string $notificationType): bool {
    return isset($this->getEnabledNotifications()[$notificationType]);
  }

}
