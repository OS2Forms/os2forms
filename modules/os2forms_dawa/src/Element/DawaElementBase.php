<?php

namespace Drupal\os2forms_dawa\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Url;

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

    if (!empty($value) && !empty($element['#autocomplete_route_name'])) {
      // Simulating autocomplete call.
      $parameters = isset($element['#autocomplete_route_parameters']) ? $element['#autocomplete_route_parameters'] : [];
      $parameters['q'] = $value;
      $url = Url::fromRoute($element['#autocomplete_route_name'], $parameters)->setAbsolute()->toString();

      $client = \Drupal::httpClient();
      $request = $client->get($url);
      $response = $request->getBody();
      $content = json_decode($response->getContents());

      // Checking if the current value is within the list of the values from an
      // autocomplete.
      if (!in_array($value, $content)) {
        $form_state->setError($element, t('"%value" has been changed. Only values from list are allowed.', ['%value' => $value]));
      }
    }
  }

}
