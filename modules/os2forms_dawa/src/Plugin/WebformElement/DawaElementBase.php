<?php

namespace Drupal\os2forms_dawa\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an Abstract DawaElementBase element.
 */
abstract class DawaElementBase extends TextField {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + $this->defineDefaultBaseProperties();
    // Remove autocomplete property which is not applicable to this autocomplete
    // element.
    unset($properties['autocomplete']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#autocomplete_route_name'] = 'os2forms_dawa.element.autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'element_type' => $element['#type'],
    ];
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

    return $form;
  }

}
