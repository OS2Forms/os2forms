<?php

namespace Drupal\os2forms_sbsys\Element;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\os2forms\Element\WebformAttachmentXml;
use Drupal\os2forms_sbsys\Plugin\WebformElement\WebformAttachmentSbsysXml as WebformElementAttachmentSbsysXml;

/**
 * Provides a 'webform_attachment_os2forms_sbsys_xml' element.
 *
 * @FormElement("webform_attachment_os2forms_sbsys_xml")
 */
class WebformAttachmentSbsysXml extends WebformAttachmentXml {

  /**
   * {@inheritdoc}
   */
  public static function getXmlContext() {
    return parent::getXmlContext() + [
      'xml_root_node_name' => 'os2formsFormular',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    $nemid_cpr = self::getFirstValueByType('os2forms_nemid_cpr', $webform_submission);
    $nemid_com_cvr = self::getFirstValueByType('os2forms_nemid_company_cvr', $webform_submission);
    $os2web_datalookup_plugins = \Drupal::service('plugin.manager.os2web_datalookup');
    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterface $sp_cpr */
    $sp_cpr = $os2web_datalookup_plugins->createInstance('serviceplatformen_cpr');
    if (!empty($nemid_cpr) && $sp_cpr->isReady()) {
      $person_address = $sp_cpr->getAddress($nemid_cpr);
      if ($person_address['status']) {
        $nemid_name = htmlspecialchars($person_address['name']);
        $nemid_address = htmlspecialchars($person_address['road'] . ' ' . $person_address['road_no'] . ' ' . $person_address['floor'] . ' ' . $person_address['door']);
        $nemid_city = htmlspecialchars($person_address['city']);
        $nemid_zipcode = htmlspecialchars($person_address['zipcode']);
      }
    }

    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterface $sp_cvr */
    $sp_cvr = $os2web_datalookup_plugins->createInstance('serviceplatformen_cvr');
    if (!empty($nemid_com_cvr) && $sp_cvr->isReady()) {
      $company_info = $sp_cvr->getInfo($nemid_com_cvr);
      if ($company_info['status']) {
        $nemid_name = htmlspecialchars($company_info['company_name']);
        $nemid_address = htmlspecialchars($company_info['company_street'] . ' ' . $company_info['company_house_nr'] . ' ' . $company_info['company_']);
        $nemid_city = htmlspecialchars($company_info['company_city']);
        $nemid_zipcode = htmlspecialchars($company_info['company_zipcode']);
      }
    }

    $config = self::getElementConfiguration($element);
    $os2formsId = self::getConfigurationValue('os2formsId', $config, $webform_submission);
    $kle = self::getConfigurationValue('kle', $config, $webform_submission);
    $sagSkabelonId = self::getConfigurationValue('sagSkabelonId', $config, $webform_submission);
    $bodyText = self::getConfigurationValue('bodyText', $config, $webform_submission);
    $nemid_cpr_mapping_value = self::getConfigurationValue('nemid_cpr', $config, $webform_submission);
    $nemid_name_mapping_value = self::getConfigurationValue('nemid_name', $config, $webform_submission);
    $nemid_address_mapping_value = self::getConfigurationValue('nemid_address', $config, $webform_submission);
    $nemid_zipcode_mapping_value = self::getConfigurationValue('nemid_zipcode', $config, $webform_submission);
    $nemid_city_mapping_value = self::getConfigurationValue('nemid_city', $config, $webform_submission);
    $maa_sendes_til_dff = isset($config['MaaSendesTilDFF']) ? $config['MaaSendesTilDFF'] : 'ja';

    if ($nemid_cpr_mapping_value && $nemid_cpr_mapping_value != 'default_nemid_value') {
      $nemid_cpr = $nemid_cpr_mapping_value;
    }
    if ($nemid_name_mapping_value && $nemid_name_mapping_value != 'default_nemid_value') {
      $nemid_name = $nemid_name_mapping_value;
    }
    if ($nemid_address_mapping_value && $nemid_address_mapping_value != 'default_nemid_value') {
      $nemid_address = $nemid_address_mapping_value;
    }
    if ($nemid_zipcode_mapping_value && $nemid_zipcode_mapping_value != 'default_nemid_value') {
      $nemid_zipcode = $nemid_zipcode_mapping_value;
    }
    if ($nemid_city_mapping_value && $nemid_city_mapping_value != 'default_nemid_value') {
      $nemid_city = $nemid_city_mapping_value;
    }

    $webform = $webform_submission->getWebform();
    $webform_title = htmlspecialchars($webform->label());
    $fields = self::getWebformElementsAsList($webform_submission);
    if (isset($fields['antal_rum_max'])) {
      $maxRoom = htmlspecialchars($fields['antal_rum_max']);
    }
    if (isset($fields['antal_rum_min'])) {
      $minRoom = htmlspecialchars($fields['antal_rum_min']);
    }
    if (isset($fields['priority_1'])) {
      $priorities = [];
      for ($i = 1; $i <= 4; $i++) {
        if ($fields['priority_' . $i]) {
          $priorities[] = htmlspecialchars($fields['priority_' . $i]);
        }
      }
    }

    $xml_data = [
      'OS2FormsId' => $os2formsId,
      'SBSYSJournalisering' => [
        'PrimaerPartCprNummer' => (!empty($nemid_cpr)) ? $nemid_cpr : '',
        'PrimaerPartCvrNummer' => (!empty($nemid_com_cvr)) ? $nemid_com_cvr : '',
        'KLe' => $kle,
        'SagSkabelonId' => $sagSkabelonId,
      ],
      'DigitalForsendelse' => [
        'Slutbruger' => [
          'CprNummer' => (isset($nemid_cpr)) ? $nemid_cpr : '',
          'CvrNummer' => (isset($nemid_com_cvr)) ? $nemid_com_cvr : '',
          'Navn' => (isset($nemid_name)) ? $nemid_name : '',
          'Adresse' => (isset($nemid_address)) ? $nemid_address : '',
          'Postnr' => (isset($nemid_zipcode)) ? $nemid_zipcode : '',
          'Postdistrikt' => (isset($nemid_city)) ? $nemid_city : '',
        ],
        'Kvittering' => [
          'TitelTekst' => $webform_title,
          'BodyTekst' => $bodyText,
        ],
        'MaaSendesTilDFF' => $maa_sendes_til_dff,
      ],
    ];

    if (isset($minRoom) || isset($maxRoom)) {
      $xml_data['Room'] = [
        'Min' => $minRoom,
        'Max' => $maxRoom,
      ];
    }

    if (!empty($priorities)) {
      $xml_data['DigitalForsendelse']['Omraade'] = implode(',', $priorities);
    }

    foreach ($fields as $field_name => $field_value) {
      $field_value = htmlspecialchars($field_value);
      $xml_data['FormularData'][$field_name] = $field_value;
    }

    return \Drupal::service('serializer')->serialize($xml_data, 'xml', [
      'xml_root_node_name' => 'os2formsFormular',
    ]);
  }

  /**
   * Retrieves configuration array from webform attachment element.
   *
   * @param array $element
   *   The webform attachment element.
   *
   * @return array
   *   Configuration array.
   */
  public static function getElementConfiguration(array $element) {
    $config = [];
    foreach (WebformElementAttachmentSbsysXml::getSbsysDefaultProperties() as $key => $value) {
      if (isset($element['#' . $key])) {
        $config[$key] = $element['#' . $key];
      }
    }
    return $config;
  }

  /**
   * Gets first element by field type.
   *
   * @param string $type
   *   Element value type.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return mixed
   *   Submission value.
   */
  protected static function getFirstValueByType($type, WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();

    $webform_elements = $webform->getElementsDecodedAndFlattened();

    foreach ($webform_elements as $key => $webform_element) {
      if ($webform_element['#type'] == $type) {
        return $webform_submission->getElementData($key);
      }
    }
  }

  /**
   * Helper function the returns the list of the fields + values as an array.
   *
   * All field except the field with type markup (used for formatting)
   * are returned.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   Webform elements as simple array.
   */
  protected static function getWebformElementsAsList(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();
    $data = $webform_submission->getData();
    $webform_elements = $webform->getElementsInitializedFlattenedAndHasValue();
    $elements_list = [];

    foreach ($webform_elements as $key => $webform_element) {
      $field_name = $key;
      $field_name = preg_replace('/\W/', '_', $field_name);
      if ($webform_element['#type'] == 'markup') {
        $elements_list[$field_name] = $webform_element['value'];
      }
      elseif ($data && isset($data[$key])) {
        $elements_list[$field_name] = $data[$key];
      }
    }
    return $elements_list;
  }

  /**
   * Fetching element configuration value from configuration array.
   *
   * @param string $name
   *   Configuration name key.
   * @param array $config
   *   Configuration array.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return string
   *   Element configuration value
   */
  public static function getConfigurationValue($name, array $config, WebformSubmissionInterface $webform_submission) {
    if ($config[$name] == 'default_nemid_value') {
      return 'default_nemid_value';
    }
    $data = $webform_submission->getData();
    if ($config[$name] != '_custom_') {
      return isset($config[$name . "_custom"]) ? htmlspecialchars($data[$config[$name]]) : '';
    }
    else {
      return isset($config[$name . "_custom"]) ? htmlspecialchars($config[$name . "_custom"]) : '';
    }
  }

}
