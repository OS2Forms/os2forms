<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

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

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($options['email']) || isset($options['pdf'])) {
      return '';
    }

    return parent::getValue($element, $webform_submission, $options);
  }

}
