<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'os2forms_nemid_cpr_fetch_data' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_cpr_fetch_data",
 *   label = @Translation("NemID CPR Fetch data"),
 *   description = @Translation("Provides a NemID CPR fetch data element."),
 *   category = @Translation("NemID"),
 *   composite = TRUE,
 * )
 *
 */
class NemidCprFetchData extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
        'fetch_button_title' => t('Hent'),
      ] + parent::getDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['cpr_fetch_data_fs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CPR fetch data settings'),
    ];
    $form['cpr_fetch_data_fs']['fetch_button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fetch button title'),
      '#description' => $this->t('The text that will be used on the fetch button to submit inserted CPR and initialize person data lookup'),
    ];

    return $form;
  }
}
