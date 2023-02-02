<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'os2forms_nemid_company_cvr_fetch_data' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_cvr_fetch_data",
 *   label = @Translation("NemID Company CVR fetch data"),
 *   description = @Translation("Provides a company CVR fetch data element."),
 *   category = @Translation("NemID"),
 *   composite = TRUE,
 * )
 */
class NemidCompanyCvrFetchData extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'fetch_button_title' => $this->t('Hent'),
    ] + parent::getDefaultProperties();
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['cvr_fetch_data_fs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CVR fetch data settings'),
    ];
    $form['cvr_fetch_data_fs']['fetch_button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fetch button title'),
      '#description' => $this->t('The text that will be used on the fetch button to submit inserted CVR and initialize company data lookup'),
    ];

    return $form;
  }

}
