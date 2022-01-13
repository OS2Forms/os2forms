<?php

namespace Drupal\os2forms_autocomplete\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class AutocompleteService.
 */
class AutocompleteService {

  /**
   * Returns a full list of items for autocomplete options.
   *
   * @param string $requestUrl
   *   URL for getting the results from.
   *
   * @return array
   *   List of options.
   */
  public function getAutocompleteItemsFromApi($requestUrl) {
    $options = [];

    $httpClient = new Client();
    try {
      $res = $httpClient->get($requestUrl);
      if ($res->getStatusCode() == 200) {
        $body = $res->getBody();
        $jsonDecoded = json_decode($body, TRUE);
        if (!empty($jsonDecoded) && is_array($jsonDecoded)) {
          foreach ($jsonDecoded as $key => $values) {
            $options = array_merge($options, $values);
          }
        }
      }
    } catch (RequestException $e) {
      \Drupal::logger('OS2Forms Autocomplete')->notice('Autocomplete request failed: %e', ['%e' => $e->getMessage()]);
    }

    return $options;
  }

  /**
   * Gets a first option from a fetched options list matching the criteria.
   *
   * @param string $requestUrl
   *   URL for getting the results from.
   * @param $needle
   *  Search criteria.
   *
   * @return mixed
   *  First available option or FALSE.
   */
  public function getFirstMatchingValue($requestUrl, $needle) {
    $options = $this->getAutocompleteItemsFromApi($requestUrl);

    if (!empty($options)) {
      foreach ($options as $option) {
        if (stripos($option, $needle) !== FALSE) {
          return $option;
        }
      }
    }

    return FALSE;
  }

}
