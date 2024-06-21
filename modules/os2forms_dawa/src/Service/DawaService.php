<?php

namespace Drupal\os2forms_dawa\Service;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\os2forms_dawa\Entity\DawaAddress;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * DAWA API service class.
 *
 * @package Drupal\os2forms_dawa\Service
 */
class DawaService {

  /**
   * DAWA Datalookup plugin.
   *
   * @var \Drupal\os2forms_dawa\Plugin\os2web\DataLookup\DawaDataLookupInterface
   */
  protected $dawaDataLookup;

  /**
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->dawaDataLookup = $manager->createInstance('dawa_data_lookup');
  }

  /**
   * Returns response for 'os2forms_dawa_address' element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   The query params.
   * @param string $fetchColumn
   *   The name of the column to return, set to NULL to get all columns.
   *
   * @return array
   *   Array of matches.
   */
  public function getAddressMatches(ParameterBag $params, $fetchColumn = 'tekst') {
    // Get autocomplete query.
    $q = $params->get('q') ?: '';

    $matches = [];

    $autocompletePath = $this->dawaDataLookup->getAddressAutocompletePath();
    $requestUrl = $autocompletePath . '?q=' . urlencode($q);

    // Adding limit by municipality limit, if present.
    $limitByMunicipality = $params->get('limit_by_municipality') ?: '';
    if (!empty($limitByMunicipality)) {
      $limit_by_municipality_arr = str_getcsv($limitByMunicipality);
      $requestUrl .= '&kommunekode=' . implode('|', $limit_by_municipality_arr);
    }

    $json = file_get_contents($requestUrl);
    $jsonDecoded = json_decode($json, TRUE);

    if ($fetchColumn) {
      if (is_array($jsonDecoded)) {
        // Checking if remove_place_name is enabled.
        $removePlaceName = $params->get('remove_place_name') ?: '';
        if ($removePlaceName) {
          foreach ($jsonDecoded as $entry) {
            $supplerendebynavn = $entry['adresse']['supplerendebynavn'];

            $text = $entry[$fetchColumn];
            if (!empty($supplerendebynavn)) {
              $text = preg_replace("/$supplerendebynavn,/", '', $text);
            }

            $matches[] = $text;
          }
        }
        else {
          $matches = array_column($jsonDecoded, $fetchColumn);
        }
      }
    }
    else {
      $matches = $jsonDecoded;
    }

    return $matches;
  }

  /**
   * Returns single address from address API.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   The query params.
   *
   * @return \Drupal\os2forms_dawa\Entity\DawaAddress|null
   *   The found address.
   */
  public function getSingleAddress(ParameterBag $params) {
    $address = NULL;

    // Getting address_id.
    $matches = $this->getAddressMatches($params, NULL);
    if (!empty($matches) && isset($matches[0]['adresse'])) {
      $address = new DawaAddress($matches[0]['adresse']);
    }

    // Fetching address.
    if ($address && $address->id()) {
      $requestUrl = $this->dawaDataLookup->getAddressApiPath() . '/' . $address->id();

      $json = file_get_contents($requestUrl);
      $jsonDecoded = json_decode($json, TRUE);

      if (is_array($jsonDecoded) && !empty($jsonDecoded)) {
        $address = new DawaAddress($jsonDecoded);
      }
    }

    return $address;
  }

  /**
   * Returns response for 'os2forms_dawa_block' element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   The query params.
   *
   * @return array
   *   Array of matches.
   */
  public function getBlockMatches(ParameterBag $params) {
    // Get autocomplete query.
    $q = $params->get('q') ?: '';

    $matches = [];

    $autocompletePath = $this->dawaDataLookup->getBlockAutocompletePath();
    $requestUrl = $autocompletePath . '?q=' . urlencode($q);

    $json = file_get_contents($requestUrl);
    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      // Checking if remove_code is enabled.
      $removeCode = $params->get('remove_code') ?: '';
      if ($removeCode) {
        foreach ($jsonDecoded as $entry) {
          $code = $entry['ejerlav']['kode'];

          $text = $entry['tekst'];
          if (!empty($code)) {
            $text = preg_replace("/$code /", '', $text);
          }

          $matches[] = $text;
        }
      }
      else {
        $matches = array_column($jsonDecoded, 'tekst');
      }
    }

    return $matches;
  }

  /**
   * Returns response for 'os2forms_dawa_matrikula' element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   The query params.
   *
   * @return array
   *   Array of matches.
   */
  public function getMatrikulaMatches(ParameterBag $params) {
    // Get autocomplete query.
    $q = $params->get('q') ?: '';

    $matches = [];

    $autocompletePath = $this->dawaDataLookup->getMatrikulaAutocompletePath();
    $requestUrl = $autocompletePath . '?q=' . urlencode($q);

    // Adding limit by municipality limit, if present.
    $limitByMunicipality = $params->get('limit_by_municipality') ?: '';
    if (!empty($limitByMunicipality)) {
      $requestUrl .= '&kommunekode=' . $limitByMunicipality;
    }

    // Adding property number, if present.
    $limitByProperty = $params->get('limit_by_property') ?: '';
    if (!empty($limitByProperty)) {
      $requestUrl .= '&esrejendomsnr=' . $limitByProperty;
    }

    $json = file_get_contents($requestUrl);
    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      // Checking if remove_code is enabled.
      $removeCode = $params->get('remove_code') ?: '';
      if ($removeCode) {
        foreach ($jsonDecoded as $entry) {
          $code = $entry['jordstykke']['ejerlav']['kode'];

          $text = $entry['tekst'];
          if (!empty($code)) {
            $text = preg_replace("/ \($code\)/", '', $text);
          }

          $matches[] = $text;
        }
      }
      else {
        $matches = array_column($jsonDecoded, 'tekst');
      }
    }

    return $matches;
  }

}
