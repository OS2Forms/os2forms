<?php

namespace Drupal\os2forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms\Utility\NameHelper;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_person_lookup' element.
 *
 * @WebformElement(
 *   id = "os2forms_person_lookup",
 *   label = @Translation("CPR / Navn validering"),
 *   description = @Translation("Giver en nem validering med CPR-nummer"),
 *   category = @Translation("OS2Forms"),
 *   composite = TRUE,
 *   multiline = TRUE,
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "os2web_datalookup",
 *   }
 * )
 *
 * @see \Drupal\address\Element\Address
 */
class Os2formsPersonLookup extends WebformCompositeBase {
  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();

    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      if (isset($properties[$composite_key . '__required'])) {
        $properties[$composite_key . '__required'] = TRUE;
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);

    $element['#element_validate'][] = [get_class($this), 'validatePerson'];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\address\Plugin\Field\FieldType\AddressItem::schema
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [
      'cpr_number' => [
        '#title' => $this->t('CPR-nummer'),
        '#type' => 'textfield',
        '#maxlength' => 255,
        '#required' => TRUE,
      ],
      'name' => [
        '#title' => $this->t('Navn'),
        '#type' => 'textfield',
        '#maxlength' => 255,
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [
      [
        'cpr_number' => '123456-7890',
        'name' => 'John Smith',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
  }

  /**
   * Form API callback. Make sure CPR is valid and belong provided name.
   */
  public static function validatePerson(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $values = $element['#value'];
    $cpr_number = str_replace('-', '', $values['cpr_number']);
    $os2web_datalookup_plugins = \Drupal::service('plugin.manager.os2web_datalookup');

    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCPR $servicePlatformentCprPlugin */
    $servicePlatformentCprPlugin = $os2web_datalookup_plugins->createInstance('serviceplatformen_cpr');
    $personsData = $servicePlatformentCprPlugin->cprBasicInformation($cpr_number);

    if (!$personsData['status']) {
      $error = isset($personsData['text']) ? $personsData['text'] : (isset($personsData['error']) ? $personsData['error'] : t('Can not verify CPR Number'));
      \Drupal::logger('os2forms')->warning(t('os2forms_person_lookup - data lookup error: @error', ['@error' => $error]));
      $form_state->setError($element['cpr_number'], t('Navn og CPR-nummer stemmer ikke overens.'));

      return;
    }

    // Do not run person lookup validation by name if it's hidden.
    if ($element['#name__access'] === FALSE) {
      return;
    }

    $helper = new NameHelper();
    if ($helper->compareNames($personsData['adresseringsnavn'], $values['name']) !== 0) {
      $form_state->setError($element['name'], t('Navn og CPR-nummer stemmer ikke overens.'));
    }
  }
}
