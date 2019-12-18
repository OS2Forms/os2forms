<?php

namespace Drupal\os2forms_dawa\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_dawa_address' element.
 *
 * @WebformElement(
 *   id = "os2forms_dawa_address",
 *   label = @Translation("DAWA Address (autocomplete)"),
 *   description = @Translation("Provides a DAWA Address Autocomplete element."),
 *   category = @Translation("DAWA"),
 * )
 */
class DawaElementAddress extends DawaElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'remove_place_name' => '',
      'limit_by_municipality' => '',
    ] + parent::getDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#autocomplete_route_parameters']['remove_place_name'] = isset($element['#remove_place_name']) ? $element['#remove_place_name'] : FALSE;
    $element['#autocomplete_route_parameters']['limit_by_municipality'] = isset($element['#limit_by_municipality']) ? $element['#limit_by_municipality'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['autocomplete']['remove_place_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove place name'),
      '#description' => $this->t('Removes the name of the place from the returned address, e.g. "Viborgvej 280, <b><i>Skave,</i></b> 7500 Holstebro" => "Viborgvej 280,  7500 Holstebro"'),
      '#return_value' => TRUE,
    ];
    $form['autocomplete']['limit_by_municipality'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit by municipality (-es)'),
      '#pattern' => '^(\d{3},?)*$',
      '#description' => $this->t('CSV list of municipalities codes, what will limit the address lookup.'),
    ];

    return $form;
  }

}
