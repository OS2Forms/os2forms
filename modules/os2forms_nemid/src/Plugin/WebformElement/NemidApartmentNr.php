<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a 'os2forms_nemid_apartment_nr' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_apartment_nr",
 *   label = @Translation("NemID Apartment nr"),
 *   description = @Translation("Provides a NemID Apartment nr element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidApartmentNr
 */
class NemidApartmentNr extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CprLookupResult::APARTMENT_NR;
  }

}
