<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'os2forms_nemid_company_p_number' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_p_number",
 *   label = @Translation("NemID Company P-Number"),
 *   description = @Translation("Provides a company P-number input elemnt."),
 *   category = @Translation("NemID"),
 *   composite = TRUE,
 * )
 *
 */
class NemidCompanyPNumber extends WebformCompositeBase {

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
    $form['p_number_fs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('P-number settings'),
    ];
    $form['p_number_fs']['fetch_button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fetch button title'),
      '#description' => $this->t('The text that will be used on the fetch button to submit inserted P-number and initialize company data lookup'),
    ];

    return $form;
  }
}
