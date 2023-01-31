<?php

namespace Drupal\os2forms_consent\Element;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\os2forms\Element\WebformAttachmentXml;
use Drupal\os2forms_consent\Plugin\WebformElement\WebformAttachmentConsentXml as WebformElementAttachmentConsentXml;

/**
 * Provides a 'webform_attachment_os2forms_consent_xml' element.
 *
 * @FormElement("webform_attachment_os2forms_consent_xml")
 */
class WebformAttachmentConsentXml extends WebformAttachmentXml {

  /**
   * {@inheritdoc}
   */
  public static function getXmlContext() {
    $xmlContext = parent::getXmlContext();
    $xmlContext['xml_root_node_name'] = 'Viljestilkendegivelseserklaering';

    return $xmlContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileName(array $element, WebformSubmissionInterface $webform_submission) {
    if (empty($element['#filename'])) {
      return 'os2forms.xml';
    }
    else {
      return parent::getFileName($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    $nemid_cpr = self::getFirstValueByType('os2forms_nemid_cpr', $webform_submission);
    $nemid_com_cvr = self::getFirstValueByType('os2forms_nemid_company_cvr', $webform_submission);

    /** @var \Drupal\os2web_datalookup\Plugin\DataLookupManager $os2web_datalookup_plugins */
    $os2web_datalookup_plugins = \Drupal::service('plugin.manager.os2web_datalookup');

    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCPRInterface $cprPlugin */
    $cprPlugin = $os2web_datalookup_plugins->createDefaultInstanceByGroup('cpr_lookup');

    if (!empty($nemid_cpr) && $cprPlugin->isReady()) {
      $cprResult = $cprPlugin->lookup($nemid_cpr);
      if ($cprResult->isSuccessful()) {
        $nemid_name = htmlspecialchars($cprResult->getName());
      }
    }
    /** @var \Drupal\os2web_datalookup\Plugin\DataLookupManager $pluginManager */
    $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');
    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCvr $cvrPlugin */
    $cvrPlugin = $pluginManager->createDefaultInstanceByGroup('cvr_lookup');

    if (!empty($nemid_com_cvr) && $cvrPlugin->isReady()) {
      $cvrResult = $cvrPlugin->lookup($nemid_com_cvr);

      if ($cvrResult->isSuccessful()) {
        $nemid_name = htmlspecialchars($cvrResult->getName());
      }
    }

    $config = self::getElementConfiguration($element);
    $nemid_cpr_mapping_value = self::getConfigurationValue('nemid_cpr', $config, $webform_submission);
    $nemid_name_mapping_value = self::getConfigurationValue('nemid_name', $config, $webform_submission);

    if ($nemid_cpr_mapping_value && $nemid_cpr_mapping_value != 'default_nemid_value') {
      $nemid_cpr = $nemid_cpr_mapping_value;
    }
    if ($nemid_name_mapping_value && $nemid_name_mapping_value != 'default_nemid_value') {
      $nemid_name = $nemid_name_mapping_value;
    }

    $fields = self::getWebformElementsAsList($webform_submission);

    $structuredData = [];
    foreach ($fields as $field_name => $field_value) {
      // Taking care of the array values.
      if (is_array($field_value)) {
        $field_value = implode(', ', $field_value);
      }

      $field_value = htmlspecialchars($field_value);
      $structuredData[$field_name] = $field_value;
    }

    $xml_data = [
      'ID' => 'urn:uuid:333e4567-e89b-12d3-a456-426655440000',
      'Attestation' => [
        'Format' => 'systembevis',
        'Underskriftstidspunkt' => '2022-07-30T12:30:06.0724725',
        'ViljestilkendegiverID' => 'urn:uuid:323e4567-e89b-12d3-a456-426655440000',
      ],
      'ErklaeringHeader' => [
        'Erklaeringsskabelon' => 'http//digst.dk/samtykke/skabeloner/personoplysninger/',
        'Adgangspolitik' => [
          'Gruppe' => 'digikoebingKommuneogLaegehus',
          'Tilladelse' => 'laes',
        ],
        'Administrator' => [
          'Administratorrolle' => 'forretningsansvarlig',
          'Organisation' => [
            'Identifikator' => [
              'Vaerdi' => '6807115500826',
              'Klassifikation' => 'EAN',
            ],
          ],
        ],
        'Aktoer' => [
          [
            'ID' => 'urn:uuid:323e4567-e89b-12d3-a456-426655440000',
            'PaakraevetSignatur' => 'true',
            'Aktoerrolle' => 'viljestilkendegivelsessubjekt',
            'Person' => [
              'Navn' => $nemid_name,
              'Identifikator' => [
                'Vaerdi' => $nemid_cpr,
                'Klassifikation' => 'CPR',
              ],
            ],
          ],
          [
            'ID' => 'urn:uuid:323e4567-e89b-12d3-a456-426655440000',
            'PaakraevetSignatur' => 'false',
            'Aktoerrolle' => 'viljetilkendegivelsesrekvirent',
            'Organisation' => [
              'Identifikator' => [
                'Vaerdi' => '11782812',
                'Klassifikation' => 'CVR',
              ],
            ],
          ],
        ],
        'Domaenekontekst' => [
          'Forvaltningshandling' => [
            'ForetrukkenTerm' => 'G01. Generelle sager',
            'Identifikator' => [
              'Vaerdi' => '27.69.24',
              'Klassifikation' => 'KLE Online Marts 2022',
            ],
          ],
          'Sagskontekst' => [
            'Titel' => 'Sag om revalidering',
            'Identifikator' => [
              'Vaerdi' => 'rvle3473-dcce-31c7-a4dc-16395242974b',
              'Klassifikation' => 'Digikøbing kommune SagsID',
            ],
          ],
        ],
        'Erklaeringsmetadata' => [
          'Status' => 'gaeldende',
          'Titel' => 'Samtykke til indhentning og/eller videregivelse af personoplysninger',
        ],
        'Viljestilkendegivelsestype' => 'samtykke',
      ],
      'ErklaeringBody' => [
        'ID' => 'urn:uuid:721a4565-e89b-12d3-a456-426655440000',
        'Erklaeringsobjekt' => [
          [
            'Format' => 'pdf',
            'DokumentFil' => 'sg015_BJ2022.07.30.pdf',
          ],
          [
            'Format' => 'tekst',
            'Tekst' => 'Jeg giver hermed mit samtykke til indhentning og deling af oplysninger fra egen læge og tidligere bopælskommune',
          ],
          [
            'Format' => 'struktureret_data',
            'Maskinlaesbartindhold' => [
              'StruktureretData' => $structuredData,
              'Stylesheet' => 'html',
            ],
          ],
        ],
      ],
    ];

    return \Drupal::service('serializer')->serialize($xml_data, 'xml', self::getXmlContext());
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
    foreach (WebformElementAttachmentConsentXml::getConsentDefaultProperties() as $key => $value) {
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
    $nemid_cpr = self::getFirstValueByType('os2forms_nemid_cpr', $webform_submission);
    $nemid_com_cvr = self::getFirstValueByType('os2forms_nemid_company_cvr', $webform_submission);
    $webform = $webform_submission->getWebform();
    $data = $webform_submission->getData();
    $webform_elements = $webform->getElementsInitializedFlattenedAndHasValue();
    $elements_list = [];
    foreach ($webform_elements as $key => $webform_element) {
      $field_name = $key;
      $field_name = preg_replace('/\W/', '_', $field_name);
      $webform_element['#type'];
      if ($webform_element['#type'] == 'markup') {
        $elements_list[$field_name] = $webform_element['value'];
      }
      elseif ($data && isset($data[$key])) {
        if ($webform_element['#type'] == 'os2forms_nemid_cpr') {
          if (!empty($nemid_cpr) && empty($nemid_com_cvr)) {
            $elements_list[$field_name] = $data[$key];
          }
        }
        else {
          $elements_list[$field_name] = $data[$key];
        }
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
      return isset($data[$config[$name]]) ? htmlspecialchars($data[$config[$name]]) : '';
    }
    else {
      return isset($config[$name . "_custom"]) ? htmlspecialchars($config[$name . "_custom"]) : '';
    }
  }

}
