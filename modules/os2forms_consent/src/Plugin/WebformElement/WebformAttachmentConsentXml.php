<?php

namespace Drupal\os2forms_consent\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms\Plugin\WebformElement\WebformAttachmentXml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_attachment_os2forms_consent_xml' element.
 *
 * @WebformElement(
 *   id = "webform_attachment_os2forms_consent_xml",
 *   label = @Translation("Declaration of Consent"),
 *   description = @Translation("Generates an xml attachment file for Consent."),
 *   category = @Translation("File attachment elements"),
 * )
 */
class WebformAttachmentConsentXml extends WebformAttachmentXml {

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
    return array_merge(parent::getDefaultProperties(), $this->getConsentDefaultProperties());
  }

  /**
   * Gets array of Consent plugin properties.
   *
   * @param bool $only_basic
   *   Flag to get only basic properties, without custom values.
   *
   * @return array
   *   Array of properties.
   */
  public static function getConsentDefaultProperties($only_basic = FALSE) {
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
  protected function getConsentPropertyType($propertyName) {
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
    $form['consent_setting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Consent settings'),
    ];

    /** @var \Drupal\webform_ui\Form\WebformUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $form = parent::form($form, $form_state);
    $webform = $form_object->getWebform();
    $elements = $webform->getElementsInitializedAndFlattened();
    $element_options = ['' => $this->t('None')];
    foreach ($elements as $element_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      $allowed_elements = [
        'textfield',
        'select',
        'email',
        'os2forms_nemid_cpr',
        'os2forms_nemid_name',
        'os2forms_nemid_pid',
        'os2forms_nemid_address',
        'os2forms_nemid_coaddress',
        'os2forms_nemid_zipcode',
        'os2forms_nemid_city',
        'os2forms_nemid_company_cvr',
        'os2forms_nemid_company_name',
        'os2forms_nemid_company_address',
        'os2forms_nemid_company_city',
        'os2forms_nemid_company_rid',
        'date',
      ];
      if (!$element_plugin->isInput($element)
        || !isset($element['#type'])
        || !in_array($element['#type'], $allowed_elements)
        || $element_plugin->hasMultipleValues($element)) {
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

    foreach ($this->getConsentDefaultProperties(TRUE) as $propertyName => $propertyDefauleValue) {
      $options = $this->getConsentPropertyType($propertyName) == 'nemid_field' ? $nemid_field_element_options : $element_options;
      $this->addConfigurationField($form, $propertyName, $propertyDefauleValue, $options, $this->getConsentPropertyType($propertyName));
    }

    $form['consent_setting']['MaaSendesTilDFF'] = [
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
    $fields = &$form['consent_setting'];
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
