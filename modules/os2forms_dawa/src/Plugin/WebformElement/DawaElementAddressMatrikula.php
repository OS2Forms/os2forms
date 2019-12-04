<?php

namespace Drupal\os2forms_dawa\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_dawa_address_matrikula' element.
 *
 * @WebformElement(
 *   id = "os2forms_dawa_address_matrikula",
 *   label = @Translation("DAWA Address-Matrikula (autocomplete)"),
 *   description = @Translation("Provides a DAWA Address Matrikula Autocomplete composite element."),
 *   category = @Translation("DAWA"),
 *   composite = TRUE,
 * )
 */
class DawaElementAddressMatrikula extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'remove_place_name' => '',
      'remove_code' => '',
      'limit_by_municipality' => '',
      'address_field_title' => t('Address'),
      'matrikula_field_title' => t('Matrikula'),
    ] + parent::getDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#webform_composite_elements']['address']['#remove_place_name'] = TRUE;
    $element['#webform_composite_elements']['address']['#autocomplete_route_parameters']['remove_place_name'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['autocomplete'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autocomplete settings'),
    ];
    $form['autocomplete']['address_field_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address field title'),
      '#description' => $this->t('The label of the field that will be used for address field.'),
    ];
    $form['autocomplete']['matrikula_field_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matrikula field title'),
      '#description' => $this->t('The label of the field that will be used for matrikula field.'),
    ];
    $form['autocomplete']['remove_place_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove place name'),
      '#description' => $this->t('Removes the name of the place from the returned address, e.g. "Viborgvej 280, <b><i>Skave,</i></b> 7500 Holstebro" => "Viborgvej 280,  7500 Holstebro"'),
      '#return_value' => TRUE,
    ];
    $form['autocomplete']['remove_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove code'),
      '#description' => $this->t('Removes the code of the place from the returned address, e.g. "1226 Agerskov Ejerlav, Agerskov <b><i>(1450151)</i></b>" => "1226 Agerskov Ejerlav, Agerskov"'),
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
