<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a abstract ServicePlatformenCpr Element.
 *
 * Implements the prepopulate logic.
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
abstract class ServiceplatformenCprElementBase extends NemidElementBase {

  /**
   * {@inheritdoc}
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state) {
    /** @var \Drupal\os2forms_nemid\Service\FormsHelper $formsHelper */
    $formsHelper = \Drupal::service('os2forms_nemid.forms_helper');
    $cprLookupResult = $formsHelper->retrieveCprLookupResult($form_state);

    if ($cprLookupResult) {
      $prepopulateKey = $this->getPrepopulateFieldFieldKey($element);
      if ($value = $cprLookupResult->getFieldValue($prepopulateKey)) {
        $element['#value'] = $value;

        // Appending name and address protection.
        if (!empty($value)) {
          $element['#value'] .= $this->appendNameAddressProtectedText($cprLookupResult);
        }
      }
    }
  }

  /**
   * Appends name/address protected text, if person has name/address protection.
   *
   * @param \Drupal\os2web_datalookup\LookupResult\CprLookupResult $cprLookupResult
   *   Initialized CprLooupResult.
   *
   * @return string
   *   String " (Navne- og adressebeskyttet)" or nothing.
   */
  public function appendNameAddressProtectedText(CprLookupResult $cprLookupResult) {
    return $cprLookupResult->isNameAddressProtected() ? ' (Navne- og adressebeskyttet)' : '';
  }

}
