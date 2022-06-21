<?php

namespace Drupal\os2forms_autocomplete\Controller;

use Drupal\webform\Controller\WebformElementController;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform elements.
 */
class AutocompleteElementController extends WebformElementController {

  /**
   * Returns response for 'os2forms_autocomplete' element autocomplete route.
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
    if ($q === '') {
      return new JsonResponse([]);
    }

    // Get the initialized webform element.
    $element = $webform->getElement($key);
    if (!$element) {
      return new JsonResponse([]);
    }

    // Loading #autocomplete_items.
    /** @var \Drupal\os2forms_autocomplete\Service\AutocompleteService $acService */
    $acService = \Drupal::service('os2forms_autocomplete.service');
    $element['#autocomplete_items'] = $acService->getAutocompleteItemsFromApi($element['#autocomplete_api_url']);

    // Set default autocomplete properties.
    $element += [
      '#autocomplete_items' => [],
      '#autocomplete_match' => 3,
      '#autocomplete_limit' => 10,
      '#autocomplete_match_operator' => 'CONTAINS',
    ];

    // Check minimum number of characters.
    if (mb_strlen($q) < (int) $element['#autocomplete_match']) {
      return new JsonResponse([]);
    }

    $matches = [];

    // Get items (aka options) matches.
    if (!empty($element['#autocomplete_items'])) {
      $element['#options'] = $element['#autocomplete_items'];
      $options = WebformOptions::getElementOptions($element);
      $matches += $this->getMatchesFromOptions($q, $options, $element['#autocomplete_match_operator'], $element['#autocomplete_limit']);
    }

    // Sort matches by label and enforce the limit.
    if ($matches) {
      uasort($matches, function (array $a, array $b) {
        return $a['label'] > $b['label'];
      });
      $matches = array_values($matches);
      $matches = array_slice($matches, 0, $element['#autocomplete_limit']);
    }

    return new JsonResponse($matches);
  }

}
