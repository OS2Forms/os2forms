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

    $elements['address'] = [
      '#type' => 'os2forms_dawa_address',
      '#title' => $element['#address_field_title'] ?? t('Address'),
      '#remove_place_name' => $element['#remove_place_name'] ?? FALSE,
      '#remove_code' => $element['#remove_code'] ?? FALSE,
      '#limit_by_municipality' => $element['#limit_by_municipality'] ?? FALSE,
    ];

    $elements['matrikula'] = [
      '#type' => 'select',
      '#title' => $element['#matrikula_field_title'] ?? t('Matrikula'),
      '#options' => [],
      '#empty_value' => NULL,
      '#validated' => TRUE,
      '#attributes' => [
        'disabled' => 'disabled',
      ],
      '#description' => t('Options autofill is disabled during the element preview'),
    ];

    // If that is just element preview (no webform_id), then keep the
    // element simple. Don't add AJAX behaviour.
    if (isset($element['#webform_id'])) {
      $matrikula_wrapper_id = $element['#webform_id'] . '-matrikula-wrapper';

      $elements['address']['#ajax'] = [
        'callback' => [
          DawaElementAddressMatrikula::class,
          'matrikulaUpdateSelectOptions',
        ],
        'event' => 'change',
        'wrapper' => $matrikula_wrapper_id,
        'progress' => [
          'type' => 'none',
        ],
      ];

      $elements['matrikula'] += [
        '#prefix' => '<div id="' . $matrikula_wrapper_id . '">',
        '#suffix' => '</div>',
      ];
      unset($elements['matrikula']['#description']);

      if (isset($element['#value']) && !empty($element['#value']['address'])) {
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
    $options = [];

    /** @var \Drupal\os2forms_dawa\Service\DawaService $dawaService */
    $dawaService = \Drupal::service('os2forms_dawa.service');

    /** @var \Drupal\os2forms_dawa\Plugin\os2web\DataLookup\DatafordelerDataLookupInterface $datafordelerLookup */
    $datafordelerLookup = \Drupal::service('plugin.manager.os2web_datalookup')->createInstance('datafordeler_data_lookup');

    // Getting address.
    $addressParams = new ParameterBag();
    $addressParams->set('q', $addressValue);
    if (isset($element['#limit_by_municipality'])) {
      $addressParams->set('limit_by_municipality', $element['#limit_by_municipality']);
    }
    $address = $dawaService->getSingleAddress($addressParams);

    if ($address) {
      $addressAccessId = $address->getAccessAddressId();

      // Find matrikula list from the houseid (husnummer):
      $matrikulaId = $datafordelerLookup->getMatrikulaId($addressAccessId);

      // Find Matrikula entries from matrikulas ID.
      if ($matrikulaId) {
        $matrikulaEnties = $datafordelerLookup->getMatrikulaEntries($matrikulaId);
        foreach ($matrikulaEnties as $matrikula) {
          $matrikulaOption = $matrikula->getMatrikulaNumber() . ' ' . $matrikula->getOwnershipName();

          if (isset($element['#remove_code']) && !$element['#remove_code']) {
            $matrikulaOption .= ' (' . $matrikula->getOwnerLicenseCode() . ')';
          }

          $options[$matrikulaOption] = $matrikulaOption;
        }
      }
    }

    return $options;
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
    $parents = $triggeringElement['#array_parents'];
    $matrikula_element = $form;
    for ($i = 0; $i <= count($parents) - 2; $i++) {
      $matrikula_element = $matrikula_element[$parents[$i]];
    }
    return $matrikula_element['matrikula'];
  }

}
