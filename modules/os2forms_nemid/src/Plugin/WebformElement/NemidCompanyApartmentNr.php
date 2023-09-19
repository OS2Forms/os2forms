<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;

/**
 * Provides a 'os2forms_nemid_company_apartment_nr' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_apartment_nr",
 *   label = @Translation("NemID Company ApartmentNr"),
 *   description = @Translation("Provides a NemID Company ApartmentNr element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyApartmentNr
 */
class NemidCompanyApartmentNr extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CompanyLookupResult::APARTMENT_NR;
  }

}
