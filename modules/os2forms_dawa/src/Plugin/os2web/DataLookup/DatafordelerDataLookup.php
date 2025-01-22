<?php

namespace Drupal\os2forms_dawa\Plugin\os2web\DataLookup;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\key\KeyRepository;
use Drupal\key\KeyRepositoryInterface;
use Drupal\os2forms_dawa\Entity\DatafordelerMatrikula;
use Drupal\os2web_audit\Service\Logger;
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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ClientInterface $httpClient,
    Logger $auditLogger,
    KeyRepositoryInterface $keyRepository,
    FileSystem $fileSystem,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $auditLogger, $keyRepository, $fileSystem);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\os2web_audit\Service\Logger $auditLogger */
    $auditLogger = $container->get('os2web_audit.logger');
    /** @var \Drupal\key\KeyRepositoryInterface $keyRepository */
    $keyRepository = $container->get('key.repository');
    /** @var \Drupal\Core\File\FileSystem $fileSystem */
    $fileSystem = $container->get('file_system');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $auditLogger,
      $keyRepository,
      $fileSystem,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMatrikulaId(string $addressAccessId) : ?string {
    $url = "https://services.datafordeler.dk/DAR/DAR/3.0.0/rest/husnummerTilJordstykke";

    $json = $this->httpClient->request('GET', $url, [
      'query' => [
        'husnummerid' => $addressAccessId,
      ],
    ])->getBody();

    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      if (NestedArray::keyExists($jsonDecoded, ['gældendeJordstykke', 'jordstykkeLokalId'])) {
        return NestedArray::getValue($jsonDecoded, ['gældendeJordstykke', 'jordstykkeLokalId']);
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatrikulaEntries(string $matrikulaId) : array {
    $matrikulaEntries = [];
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
      if (NestedArray::keyExists($jsonDecoded, ['features', 0, 'properties', 'jordstykke'])) {
        $jordstykker = NestedArray::getValue($jsonDecoded, ['features', 0, 'properties', 'jordstykke']);
        foreach ($jordstykker as $jordstyk) {
          $matrikulaEntries[] = new DatafordelerMatrikula($jordstyk);
        }
      }
    }

    return $matrikulaEntries;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
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
