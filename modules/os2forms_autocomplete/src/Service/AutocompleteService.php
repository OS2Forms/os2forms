<?php

namespace Drupal\os2forms_autocomplete\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for the OS2Forms Autocomplete.
 */
class AutocompleteService {

  /**
   * The OS2Forms logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * AutocompleteService constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactory $logger_factory) {
    $this->logger = $logger_factory->get('OS2Forms Autocomplete');
  }

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
          foreach ($jsonDecoded as $values) {
            $options = array_merge($options, $values);
          }
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->notice('Autocomplete request failed: %e', ['%e' => $e->getMessage()]);
    }

    return $options;
  }

  /**
   * Gets a first option from a fetched options list matching the criteria.
   *
   * @param string $requestUrl
   *   URL for getting the results from.
   * @param string $needle
   *   Search criteria.
   *
   * @return mixed
   *   First available option or FALSE.
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
