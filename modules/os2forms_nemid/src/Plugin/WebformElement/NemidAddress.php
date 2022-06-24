<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_nemid_address' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_address",
 *   label = @Translation("NemID Address"),
 *   description = @Translation("Provides a NemID Address element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidAddress
 */
class NemidAddress extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return 'address';
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    parent::alterForm($element, $form, $form_state);

    $spCrpData = $form_state->get('servicePlatformenCprData');

    /** @var \Drupal\webform\WebformSubmissionForm $webformSubmissionForm */
    $webformSubmissionForm = $form_state->getFormObject();

    /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
    $webformSubmission = $webformSubmissionForm->getEntity();

    // Only manipulate element on submission create form.
    if (!$webformSubmission->isCompleted()) {
      if ($spCrpData['name_address_protected']) {
        $element['#info_message'] = 'adresse beskyttelse';
        NestedArray::setValue($form['elements'], $element['#webform_parents'], $element);
        $form['actions']['submit']['#submit'][] = 'os2forms_nemid_submission_set_address_protected';
      }
    }
    else {
      $data = $webformSubmission->getData();
      if (array_key_exists('os2forms_nemid_elements_nemid_address_protected', $data)) {
        $element['#description'] = $this->t('(adresse beskyttelse)');
        NestedArray::setValue($form['elements'], $element['#webform_parents'], $element);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = parent::getValue($element, $webform_submission, $options = []);

    $data = $webform_submission->getData();
    if (array_key_exists('os2forms_nemid_elements_nemid_address_protected', $data)) {
      $value .= ' (adresse beskyttelse)';
    }

    return $value;
  }

}
