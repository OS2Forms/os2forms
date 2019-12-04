<?php

namespace Drupal\os2forms_dawa\Plugin\os2web\DataLookup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupBase;

/**
 * Defines a plugin for Dawa Data.
 *
 * @DataLookup(
 *   id = "dawa_data_lookup",
 *   label = @Translation("Danish Addresses Web API (DAWA) Lookup"),
 * )
 */
class DawaDataLookup extends DataLookupBase implements DawaDataLookupInterface {

  /**
   * {@inheritdoc}
   */
  public function getAddressAutocompletePath() {
    return $this->configuration['address_autocomplete_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressApiPath() {
    return $this->configuration['address_api_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockAutocompletePath() {
    return $this->configuration['block_autocomplete_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMatrikulaAutocompletePath() {
    return $this->configuration['matrikula_autocomplete_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'address_autocomplete_path' => 'https://dawa.aws.dk/adresser/autocomplete',
      'address_api_path' => 'https://dawa.aws.dk/adresser',
      'block_autocomplete_path' => 'https://dawa.aws.dk/ejerlav/autocomplete',
      'matrikula_autocomplete_path' => 'https://dawa.aws.dk/jordstykker/autocomplete',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['address_autocomplete_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address autocomplete path'),
      '#default_value' => $this->configuration['address_autocomplete_path'],
      '#required' => TRUE,
      '#description' => $this->t('API path providing the address autocomplete values. Default value: https://dawa.aws.dk/adresser/autocomplete'),
    ];
    $form['address_api_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address API path'),
      '#default_value' => $this->configuration['address_api_path'],
      '#required' => TRUE,
      '#description' => $this->t('API path providing the address values. Default value: https://dawa.aws.dk/adresser'),
    ];
    $form['block_autocomplete_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block autocomplete path'),
      '#default_value' => $this->configuration['block_autocomplete_path'],
      '#required' => TRUE,
      '#description' => $this->t('API path providing the block autocomplete values. Default value: https://dawa.aws.dk/ejerlav/autocomplete'),
    ];
    $form['matrikula_autocomplete_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matrikula autocomplete path'),
      '#default_value' => $this->configuration['matrikula_autocomplete_path'],
      '#required' => TRUE,
      '#description' => $this->t('API path providing the matrikula autocomplete values. Default value: https://dawa.aws.dk/jordstykker/autocomplete'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Validating 'address_autocomplete_path', 'block_autocomplete_path',
    // 'matrikula_autocomplete_path'.
    $elementsToValidate = [
      'address_autocomplete_path',
      'block_autocomplete_path',
      'matrikula_autocomplete_path',
    ];
    foreach ($elementsToValidate as $elementKey) {
      $autocomplete_path = $form_state->getValue($elementKey);
      $json = file_get_contents($autocomplete_path);
      $jsonDecoded = json_decode($json, TRUE);
      if (empty($jsonDecoded)) {
        $form_state->setErrorByName($elementKey, $this->t('URL is not valid or it does not provide the result in the required format'));
      }
      else {
        $entry = reset($jsonDecoded);
        if (!array_key_exists('tekst', $entry)) {
          $form_state->setErrorByName($elementKey, $this->t('URL is not valid or it does not provide the result in the required format'));
        }
      }
    }

    // Validating address_api_path.
    $autocomplete_path = $form_state->getValue('address_api_path');
    // Limiting the output.
    $json = file_get_contents($autocomplete_path . '?per_side=1');
    $jsonDecoded = json_decode($json, TRUE);
    if (empty($jsonDecoded)) {
      $form_state->setErrorByName('address_api_path', $this->t('URL is not valid or it does not provide the result in the required format'));
    }
    else {
      $entry = reset($jsonDecoded);
      if (!array_key_exists('id', $entry)) {
        $form_state->setErrorByName('address_api_path', $this->t('URL is not valid or it does not provide the result in the required format'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['address_autocomplete_path'] = $form_state->getValue('address_autocomplete_path');
    $configuration['address_api_path'] = $form_state->getValue('address_api_path');
    $configuration['block_autocomplete_path'] = $form_state->getValue('block_autocomplete_path');
    $configuration['matrikula_autocomplete_path'] = $form_state->getValue('matrikula_autocomplete_path');
    $this->setConfiguration($configuration);
  }

}
