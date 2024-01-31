<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Hidden;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'os2forms_mitid_child_other_guardian' element.
 *
 * @WebformElement(
 *   id = "os2forms_mitid_child_other_guardian",
 *   label = @Translation("MitID Child Other Guardian"),
 *   description = @Translation("Provides a MitID Child Other Guardian element."),
 *   category = @Translation("NemID"),
 * )
 */
class MitidChildOtherGuardian extends Hidden {

  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($options['email'])) {
      return '';
    }

    return parent::getValue($element, $webform_submission, $options);
  }
}
