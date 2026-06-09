<?php

namespace Drupal\os2forms_dawa\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides an abstract Base Element for DAWA elements.
 */
abstract class DawaElementBase extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    $info = parent::getInfo();
    $info['#element_validate'][] = [$class, 'validateDawaElementBase'];
    return $info;
  }

  /**
   * Webform element validation handler for DawaElementBase.
   */
  public static function validateDawaElementBase(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#webform_key'])) {
      $value = $form_state->getValue($element['#webform_key']);
    }
    else {
      $value = $form_state->getValue($element['#parents']);
    }

    if (!empty($value)) {
      $matches = [];

      if ($element['#type'] == 'os2forms_dawa_address') {
        /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DatafordelerAddressLookupInterface $datafordelerAddressLookup */
        $datafordelerAddressLookup = \Drupal::service('plugin.manager.os2web_datalookup')->createInstance('datafordeler_address_lookup');

        $parameters = new ParameterBag($element['#autocomplete_route_parameters']);
        $parameters->set('q', $value);
        $matches = $datafordelerAddressLookup->getAddressMatches($parameters);
      }

      // Checking if the current value is within the list of the values from an
      // autocomplete.
      if (!in_array($value, $matches)) {
        $form_state->setError($element, t('"%value" has been changed. Only values from list are allowed.', ['%value' => $value]));
      }
    }
  }

}
