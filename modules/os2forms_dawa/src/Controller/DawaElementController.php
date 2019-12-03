<?php

namespace Drupal\os2forms_dawa\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform elements.
 */
class DawaElementController extends ControllerBase {

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
    $this->dawaDataLookup = $manager->createInstance('dawa_address');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.os2web_datalookup')
    );
  }

  /**
   * Returns response for 'os2forms_dawa' element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $key
   *   Webform element key.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, WebformInterface $webform, $key) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';
    if ($q == '') {
      return new JsonResponse([]);
    }

    // Get the initialized webform element.
    $element = $webform->getElement($key);
    if (!$element) {
      return new JsonResponse([]);
    }

    $matches = [];

    // Get the matches based on the element type.
    switch ($element['#type']) {
      case 'os2forms_dawa_address':
        $matches = $this->getAddressAutocompleteMatches($request);
        break;

      case 'os2forms_dawa_block':
        $matches = $this->getblockAutocompleteMatches($request);
        break;

      case 'os2forms_dawa_matrikula':
        $matches = $this->getMatrikulaAutocompleteMatches($request);
        break;
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns response for 'os2forms_dawa_address' element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return array
   *   Array of matches.
   */
  public function getAddressAutocompleteMatches(Request $request) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';

    $matches = [];

    $autocompletePath = $this->dawaDataLookup->getAddressAutocompletePath();
    $requestUrl = $autocompletePath . '?q=' . urlencode($q);

    // Adding limit by municipality limit, if present.
    $limitByMunicipality = $request->query->get('limit_by_municipality') ?: '';
    if (!empty($limitByMunicipality)) {
      $limit_by_municipality_arr = str_getcsv($limitByMunicipality);
      $requestUrl .= '&kommunekode=' . implode('|', $limit_by_municipality_arr);
    }

    $json = file_get_contents($requestUrl);
    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      // Checking if remove_place_name is enabled.
      $removePlaceName = $request->query->get('remove_place_name') ?: '';
      if ($removePlaceName) {
        foreach ($jsonDecoded as $entry) {
          $supplerendebynavn = $entry['adresse']['supplerendebynavn'];

          $text = $entry['tekst'];
          if (!empty($supplerendebynavn)) {
            $text = preg_replace("/$supplerendebynavn,/", '', $text);
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
   * Returns response for 'os2forms_dawa_block' element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return array
   *   Array of matches.
   */
  public function getblockAutocompleteMatches(Request $request) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';

    $matches = [];

    $autocompletePath = $this->dawaDataLookup->getBlockAutocompletePath();
    $requestUrl = $autocompletePath . '?q=' . urlencode($q);

    $json = file_get_contents($requestUrl);
    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      // Checking if remove_code is enabled.
      $removeCode = $request->query->get('remove_code') ?: '';
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return array
   *   Array of matches.
   */
  public function getMatrikulaAutocompleteMatches(Request $request) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';

    $matches = [];

    $autocompletePath = $this->dawaDataLookup->getMatrikulaAutocompletePath();
    $requestUrl = $autocompletePath . '?q=' . urlencode($q);

    // Adding limit by municipality limit, if present.
    $limitByMunicipality = $request->query->get('limit_by_municipality') ?: '';
    if (!empty($limitByMunicipality)) {
      $requestUrl .= '&kommunekode=' . $limitByMunicipality;
    }

    $json = file_get_contents($requestUrl);
    $jsonDecoded = json_decode($json, TRUE);
    if (is_array($jsonDecoded)) {
      // Checking if remove_code is enabled.
      $removeCode = $request->query->get('remove_code') ?: '';
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
