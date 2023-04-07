<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a 'os2forms_nemid_postal_code' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_postal_code",
 *   label = @Translation("NemID Postal code"),
 *   description = @Translation("Provides a NemID Postal code element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidPostalCode
 */
class NemidPostalCode extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CprLookupResult::POSTAL_CODE;
  }

}
