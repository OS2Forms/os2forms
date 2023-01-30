<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a abstract ServicePlatformenCompany Element.
 *
 * Implements the prepopulate logic.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class ServiceplatformenCompanyElementBase extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
    /** @var \Drupal\os2forms_nemid\Service\FormsHelper $formsHelper */
    $formsHelper = \Drupal::service('os2forms_nemid.forms_helper');
    $cvrLookupResult = $formsHelper->retrieveCvrLookupResult($form_state);

    if ($cvrLookupResult) {
      $prepopulateKey = $this->getPrepopulateFieldFieldKey();
      if ($value = $cvrLookupResult->getFieldValue($prepopulateKey)) {
        $element['#value'] = $value;
      }
    }
  }

}
