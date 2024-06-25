<?php

namespace Drupal\os2forms_dawa\Plugin\os2web\DataLookup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_dawa\Entity\DatafordelerMatrikula;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \GuzzleHttp\ClientInterface
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
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMatrikulaIds(string $addressAccessId) : array {
    $url = "https://services.datafordeler.dk/BBR/BBRPublic/1/rest/grund";

    $configuration = $this->getConfiguration();
    $json = $this->httpClient->request('GET', $url, [
      'query' => [
        'husnummer' => $addressAccessId,
        'status' => 7,
        'username' => $configuration['username'],
        'password' => $configuration['password'],
      ],
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
        'password' => $configuration['password'],
      ],
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['username'] = $form_state->getValue('username');
    $configuration['password'] = $form_state->getValue('password');
    $this->setConfiguration($configuration);
  }

}
