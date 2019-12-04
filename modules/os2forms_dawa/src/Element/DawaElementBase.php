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
      /** @var \Drupal\os2forms_dawa\Service\DawaService $dawaService*/
      $dawaService = \Drupal::service('os2forms_dawa.service');

      $element_type = $element['#type'];

      $parameters = new ParameterBag($element['#autocomplete_route_parameters']);
      $parameters->set('q', $value);

      switch ($element_type) {
        case 'os2forms_dawa_address':
          $matches = $dawaService->getAddressMatches($parameters);
          break;

        case 'os2forms_dawa_block':
          $matches = $dawaService->getBlockMatches($parameters);
          break;

        case 'os2forms_dawa_matrikula':
          $matches = $dawaService->getMatrikulaMatches($parameters);
          break;
      }

      // Checking if the current value is within the list of the values from an
      // autocomplete.
      if (!in_array($value, $matches)) {
        $form_state->setError($element, t('"%value" has been changed. Only values from list are allowed.', ['%value' => $value]));
      }
    }
  }

}
