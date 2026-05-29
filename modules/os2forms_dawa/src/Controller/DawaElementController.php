<?php

namespace Drupal\os2forms_dawa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform elements.
 */
class DawaElementController extends ControllerBase {

  /**
   * Datafordeler address lookup.
   *
   * @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DatafordelerAddressLookupInterface
   */
  protected $datafordelerAddressLookup;

  /**
   * Constructs a DawaElementController object.
   *
   * @param \Drupal\os2web_datalookup\Plugin\DataLookupManager $dataLookupManager
   *   Datalookup manager.
   */
  public function __construct(DataLookupManager $dataLookupManager) {
    $this->datafordelerAddressLookup = $dataLookupManager->createInstance('datafordeler_address_lookup');
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
   * @param string $element_type
   *   Type of the webform element.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, $element_type) {
    // Get autocomplete query.
    $query = $request->query;
    $q = $query->get('q') ?: '';
    if (!is_string($q) || $q == '') {
      return new JsonResponse([]);
    }

    $matches = [];

    // Get the matches based on the element type.
    if ($element_type == 'os2forms_dawa_address') {
      $matches = $this->datafordelerAddressLookup->getAddressMatches($query);
    }

    return new JsonResponse($matches);
  }

}
