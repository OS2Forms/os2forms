<?php

namespace Drupal\os2forms_dawa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform elements.
 */
class DawaElementController extends ControllerBase {

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

    /** @var \Drupal\os2forms_dawa\Service\DawaService $dawaService*/
    $dawaService = \Drupal::service('os2forms_dawa.service');

    // Get the matches based on the element type.
    switch ($element_type) {
      case 'os2forms_dawa_address':
        $matches = $dawaService->getAddressMatches($query);
        break;

      case 'os2forms_dawa_block':
        $matches = $dawaService->getBlockMatches($query);
        break;

      case 'os2forms_dawa_matrikula':
        $matches = $dawaService->getMatrikulaMatches($query);
        break;
    }

    return new JsonResponse($matches);
  }

}
