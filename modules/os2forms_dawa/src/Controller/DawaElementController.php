<?php

namespace Drupal\os2forms_dawa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_dawa\Service\DawaService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform elements.
 */
class DawaElementController extends ControllerBase {

  /**
   * The DAWA service object.
   *
   * @var \Drupal\os2forms_dawa\Service\DawaService
   */
  protected $dawaService;

  /**
   * Constructs a DawaElementController object.
   *
   * @param \Drupal\os2forms_dawa\Service\DawaService $os2forms_dawa_service
   *   The DAWA service object.
   */
  public function __construct(DawaService $os2forms_dawa_service) {
    $this->dawaService = $os2forms_dawa_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2forms_dawa.service')
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
    switch ($element_type) {
      case 'os2forms_dawa_address':
        $matches = $this->dawaService->getAddressMatches($query);
        break;

      case 'os2forms_dawa_block':
        $matches = $this->dawaService->getBlockMatches($query);
        break;

      case 'os2forms_dawa_matrikula':
        $matches = $this->dawaService->getMatrikulaMatches($query);
        break;
    }

    return new JsonResponse($matches);
  }

}
