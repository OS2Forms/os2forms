<?php

namespace Drupal\os2forms_dawa\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_dawa_block' element.
 *
 * @WebformElement(
 *   id = "os2forms_dawa_block",
 *   label = @Translation("DAWA Block (autocomplete)"),
 *   description = @Translation("Provides a DAWA Block Autocomplete element."),
 *   category = @Translation("DAWA"),
 * )
 */
class DawaBlock extends DawaElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'remove_code' => '',
    ] + parent::getDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#autocomplete_route_name'] = 'os2forms_dawa.element.autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'webform' => $webform_submission->getWebform()->id(),
      'key' => $element['#webform_key'],
      'remove_code' => isset($element['#remove_code']) ? $element['#remove_code'] : FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['autocomplete']['remove_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove code'),
      '#description' => $this->t('Removes the code from the block address, e.g. "<b><i>1450151</i></b> Agerskov Ejerlav, Agerskov" => "Agerskov Ejerlav, Agerskov"'),
      '#return_value' => TRUE,
    ];

    return $form;
  }

}
