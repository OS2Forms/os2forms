<?php

namespace Drupal\os2forms_sbsys\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms\Plugin\WebformElement\WebformAttachmentXml;
use Drupal\os2forms_nemid\Plugin\WebformElement\NemidElementBase;
use Drupal\webform\Plugin\WebformElement\DateBase;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\Plugin\WebformElement\WebformComputedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_attachment_os2forms_sbsys_xml' element.
 *
 * @WebformElement(
 *   id = "webform_attachment_os2forms_sbsys_xml",
 *   label = @Translation("Attachment SBSYS xml"),
 *   description = @Translation("Generates an xml attachment file for SBSYS."),
 *   category = @Translation("File attachment elements"),
 * )
 */
class WebformAttachmentSbsysXml extends WebformAttachmentXml {

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return array_merge(parent::getDefaultProperties(), $this->getSbsysDefaultProperties());
  }

  /**
   * Gets array of SBSYS plugin properties.
   *
   * @param bool $only_basic
   *   Flag to get only basic properties, without custom values.
   *
   * @return array
   *   Array of properties.
   */
  public static function getSbsysDefaultProperties($only_basic = FALSE) {
    $basic_properties = [
      'os2formsId' => '',
      'kle' => '',
      'sagSkabelonId' => '',
      'nemid_cpr' => '',
      'nemid_name' => '',
      'nemid_address' => '',
      'nemid_zipcode' => '',
      'nemid_city' => '',
      'bodyText' => '',
    ];
    if ($only_basic) {
      return $basic_properties;
    }

    $properties = $basic_properties;
    foreach ($basic_properties as $key => $value) {
      $properties[$key . '_custom'] = '';
    }
    $properties['MaaSendesTilDFF'] = '';
    $properties['filename'] = 'os2forms.xml';
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSbsysPropertyType($propertyName) {
    $propertyTypes = [
      'os2formsId' => 'textfield',
      'kle' => 'textfield',
      'sagSkabelonId' => 'textfield',
      'nemid_cpr' => 'nemid_field',
      'nemid_name' => 'nemid_field',
      'nemid_address' => 'nemid_field',
      'nemid_zipcode' => 'nemid_field',
      'nemid_city' => 'nemid_field',
      'bodyText' => 'textarea',
    ];
    return $propertyTypes[$propertyName] ?? 'textfield';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['sbsys_setting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SBSYS settings'),
    ];

    /** @var \Drupal\webform_ui\Form\WebformUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $form = parent::form($form, $form_state);
    $webform = $form_object->getWebform();
    $elements = $webform->getElementsInitializedAndFlattened();
    $element_options = ['' => $this->t('None')];
    foreach ($elements as $element_key => $element) {
      $elementInstance = $this->elementManager->getElementInstance($element);

      // Skipping if not input or has multiple values.
      if (!$elementInstance->isInput($element)
        || $elementInstance->hasMultipleValues($element)) {
        continue;
      }

      // Skipping if is of type we do not support.
      if (!$elementInstance instanceof TextBase && !$elementInstance instanceof NemidElementBase && !$elementInstance instanceof DateBase && !$elementInstance instanceof WebformComputedBase) {
        continue;
      }

      $element_options[$element_key] = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', [
        '@title' => $element['#title'],
        '@key' => $element_key,
      ]) : $element_key;
    }
    $element_options['_custom_'] = $this->t('Custom text ...');
    $nemid_field_element_options = array_merge(
      [
        '' => $this->t('None'),
        'default_nemid_value' => $this->t('Get value from nemid'),
      ],
      $element_options
    );
    foreach ($this->getSbsysDefaultProperties(TRUE) as $propertyName => $propertyDefauleValue) {
      $options = $this->getSbsysPropertyType($propertyName) == 'nemid_field' ? $nemid_field_element_options : $element_options;
      $this->addConfigurationField($form, $propertyName, $propertyDefauleValue, $options, $this->getSbsysPropertyType($propertyName));
    }

    $form['sbsys_setting']['MaaSendesTilDFF'] = [
      '#type' => 'select',
      '#title' => $this->t('MaaSendesTilDFF value'),
      '#options' => ['ja' => 'Ja', 'nej' => 'Nej'],
      '#description' => $this->t('Select a value from form submitted fields or provide a custom static value'),
    ];

    return $form;
  }

  /**
   * Helper method that defines configuration field with custom value.
   */
  protected function addConfigurationField(&$form, $field_name, $default, $components_options, $field_type = 'textfield') {
    $fields = &$form['sbsys_setting'];
    $fields[$field_name] = [
      '#type' => 'select',
      '#title' => $this->t('@field_name value', ['@field_name' => $field_name]),
      '#options' => $components_options,
      '#description' => $this->t('Select a value from form submitted fields or provide a custom static value'),
    ];
    if ($field_type == 'nemid_field' && empty($default[$field_name])) {
      $fields[$field_name]['#default_value'] = 'default_nemid_value';
    }
    if ($field_type == 'textfield' || $field_type == 'nemid_field') {
      $fields[$field_name . '_custom'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@field_name custom text', ['@field_name' => $field_name]),
        '#size' => 60,
        '#maxlength' => 128,
        '#states' => [
          'visible' => [
            'select[name="properties[' . $field_name . ']"]' => ['value' => '_custom_'],
          ],
        ],
        '#description' => $this->t('Provide a custom static value'),
      ];
    }
    else {
      $fields[$field_name . '_custom'] = [
        '#type' => 'textarea',
        '#title' => $this->t('@field_name custom text', ['@field_name' => $field_name]),
        '#states' => [
          'visible' => [
            'select[name="properties[' . $field_name . ']"]' => ['value' => '_custom_'],
          ],
        ],
        '#description' => $this->t('Provide a custom static value'),
      ];
    }
  }

}
