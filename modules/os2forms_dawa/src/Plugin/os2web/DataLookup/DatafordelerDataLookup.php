<?php

namespace Drupal\os2forms_dawa\Plugin\os2web\DataLookup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_dawa\Entity\DatafordelerMatrikula;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Defines a plugin for Datafordeler Data.
 *
 * @DataLookup(
 *   id = "datafordeler_data_lookup",
 *   label = @Translation("Datafordeler Address Lookup"),
 * )
 */
class DatafordelerDataLookup extends DataLookupBase implements DatafordelerDataLookupInterface, ContainerFactoryPluginInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMatrikulaIds(string $addressAccessId) : array {
    $url = "https://services.datafordeler.dk/BBR/BBRPublic/1/rest/grund";//

    $configuration = $this->getConfiguration();
    $json = $this->httpClient->request('GET', $url, [
      'query' => [
        'husnummer' => $addressAccessId,
        'status' => 7,
        'username' => $configuration['username'],
        'password' => $configuration['password']
      ]
    ])->getBody();

    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      return $jsonDecoded[0]['jordstykkeList'];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMatrikulaEntry(string $matrikulaId) : ?DatafordelerMatrikula {
    $url = "https://services.datafordeler.dk/Matriklen2/Matrikel/2.0.0/rest/SamletFastEjendom";

    $configuration = $this->getConfiguration();
    $json = $this->httpClient->request('GET', $url, [
      'query' => [
        'jordstykkeid' => $matrikulaId,
        'username' => $configuration['username'],
        'password' => $configuration['password']
      ]
    ])->getBody();

    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      return new DatafordelerMatrikula($jsonDecoded);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'username' => '',
      'password' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username for service calls'),
      '#default_value' => $this->configuration['username'],
      '#required' => TRUE,
      '#description' => $this->t('Username required for performing API requests'),
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password for service calls'),
      '#default_value' => $this->configuration['password'],
      '#required' => TRUE,
      '#description' => $this->t('Password required for performing API requests'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

//    // Validating 'address_autocomplete_path', 'block_autocomplete_path',
//    // 'matrikula_autocomplete_path'.
//    $elementsToValidate = [
//      'address_autocomplete_path',
//      'block_autocomplete_path',
//      'matrikula_autocomplete_path',
//    ];
//    foreach ($elementsToValidate as $elementKey) {
//      $autocomplete_path = $form_state->getValue($elementKey);
//      $json = file_get_contents($autocomplete_path);
//      $jsonDecoded = json_decode($json, TRUE);
//      if (empty($jsonDecoded)) {
//        $form_state->setErrorByName($elementKey, $this->t('URL is not valid or it does not provide the result in the required format'));
//      }
//      else {
//        $entry = reset($jsonDecoded);
//        if (!array_key_exists('tekst', $entry)) {
//          $form_state->setErrorByName($elementKey, $this->t('URL is not valid or it does not provide the result in the required format'));
//        }
//      }
//    }
//
//    // Validating address_api_path.
//    $autocomplete_path = $form_state->getValue('address_api_path');
//    // Limiting the output.
//    $json = file_get_contents($autocomplete_path . '?per_side=1');
//    $jsonDecoded = json_decode($json, TRUE);
//    if (empty($jsonDecoded)) {
//      $form_state->setErrorByName('address_api_path', $this->t('URL is not valid or it does not provide the result in the required format'));
//    }
//    else {
//      $entry = reset($jsonDecoded);
//      if (!array_key_exists('id', $entry)) {
//        $form_state->setErrorByName('address_api_path', $this->t('URL is not valid or it does not provide the result in the required format'));
//      }
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['username'] = $form_state->getValue('username');
    $configuration['password'] = $form_state->getValue('password');
    $this->setConfiguration($configuration);
  }
}
