<?php

namespace Drupal\os2forms_dawa\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_dawa_matrikula' element.
 *
 * @WebformElement(
 *   id = "os2forms_dawa_matrikula",
 *   label = @Translation("DAWA Matrikula (autocomplete)"),
 *   description = @Translation("Provides a DAWA Matrikula Autocomplete element."),
 *   category = @Translation("DAWA"),
 * )
 */
class DawaElementMatrikula extends DawaElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'remove_code' => '',
      'limit_by_municipality' => '',
    ] + parent::getDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#autocomplete_route_parameters']['remove_code'] = isset($element['#remove_code']) ? $element['#remove_code'] : FALSE;
    $element['#autocomplete_route_parameters']['limit_by_municipality'] = isset($element['#limit_by_municipality']) ? $element['#limit_by_municipality'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['autocomplete']['remove_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove code'),
      '#description' => $this->t('Removes the code of the place from the returned address, e.g. "1226 Agerskov Ejerlav, Agerskov <b><i>(1450151)</i></b>" => "1226 Agerskov Ejerlav, Agerskov"'),
      '#return_value' => TRUE,
    ];
    $form['autocomplete']['limit_by_municipality'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit by municipality'),
      '#pattern' => '^\d{3}$',
      '#description' => $this->t('Single municipality codes, what will limit the address lookup (single three digit municipality code)'),
    ];

    return $form;
  }

}
