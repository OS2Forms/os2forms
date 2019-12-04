<?php

namespace Drupal\os2forms_dawa\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides a DAWA Address Autocomplete element.
 *
 * @FormElement("os2forms_dawa_address_matrikula")
 */
class DawaElementAddressMatrikula extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    if ($element) {
      $matrikula_wrapper_id = $element['#webform_id'] . '-matrikula-wrapper';

      $elements['address'] = [
        '#type' => 'os2forms_dawa_address',
        '#title' => isset($element['#address_field_title']) ? $element['#address_field_title'] : t('Address'),
        '#ajax' => [
          'callback' => [DawaElementAddressMatrikula::class, 'matrikulaUpdateSelectOptions'],
          'event' => 'change',
          'wrapper' => $matrikula_wrapper_id,
          'progress' => [
            'type' => 'none',
          ],
        ],
        '#remove_place_name' => isset($element['#remove_place_name']) ? $element['#remove_place_name'] : FALSE,
        '#remove_code' => isset($element['#remove_code']) ? $element['#remove_code'] : FALSE,
        '#limit_by_municipality' => isset($element['#limit_by_municipality']) ? $element['#limit_by_municipality'] : FALSE,
      ];

      $elements['matrikula'] = [
        '#type' => 'select',
        '#title' => isset($element['#matrikula_field_title']) ? $element['#matrikula_field_title'] : t('Matrikula'),
        '#prefix' => '<div id="' . $matrikula_wrapper_id . '">',
        '#suffix' => '</div>',
        '#options' => [],
        '#empty_value' => NULL,
        '#validated' => TRUE,
        '#attributes' => [
          'disabled' => 'disabled',
        ],
      ];

      if (isset($element['#value']) && isset($element['#value']['address'])) {
        $addressValue = $element['#value']['address'];

        $matrikulaOptions = self::getMatrikulaOptions($addressValue, $element);

        // Populating the element.
        if (!empty($matrikulaOptions)) {
          $elements['matrikula']['#options'] = $matrikulaOptions;
          $matrikulaOptionKeys = array_keys($matrikulaOptions);
          $elements['matrikula']['matrikula']['#value'] = reset($matrikulaOptionKeys);

          // Make element enabled.
          unset($elements['matrikula']['#attributes']['disabled']);
        }
      }
    }

    return $elements;
  }

  /**
   * Fetches the matrikula options and returns them.
   *
   * @param string $addressValue
   *   The value from address field.
   * @param array $element
   *   Element of type 'os2forms_dawa_address_matrikula'.
   *
   * @return array
   *   Array of matrikula options key and the values are identical.
   */
  private static function getMatrikulaOptions($addressValue, array $element) {
    /** @var \Drupal\os2forms_dawa\Service\DawaService $dawaService */
    $dawaService = \Drupal::service('os2web_dawa.service');

    // Getting address.
    $addressParams = new ParameterBag();
    $addressParams->set('q', $addressValue);
    if (isset($element['#limit_by_municipality'])) {
      $addressParams->set('limit_by_municipality', $element['#limit_by_municipality']);
    }
    $address = $dawaService->getSingleAddress($addressParams);

    if ($address) {
      // Getting matrikula options.
      $matrikulaParams = new ParameterBag();
      // Getting municipality code from address.
      if ($municipality_code = $address->getMunicipalityCode()) {
        $matrikulaParams->set('limit_by_municipality', $municipality_code);
      }
      // Getting property nr from address.
      if ($property_nr = $address->getPropertyNumber()) {
        $matrikulaParams->set('limit_by_property', $property_nr);
      }
      // If the matrikula option must not have the code.
      if (isset($element['#remove_code'])) {
        $matrikulaParams->set('remove_code', $element['#remove_code']);
      }

      // Get the options.
      $matrikulaOptions = $dawaService->getMatrikulaMatches($matrikulaParams);

      // Use values as keys.
      return array_combine($matrikulaOptions, $matrikulaOptions);
    }

    return [];
  }

  /**
   * Updates the available options for matrikula select field.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   Matrikula select component.
   */
  public static function matrikulaUpdateSelectOptions(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $parents = $triggeringElement['#parents'];
    $parentName = $parents[count($parents) - 2];

    return $form['elements'][$parentName]['matrikula'];
  }

}
