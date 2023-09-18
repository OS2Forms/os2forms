<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;

/**
 * Provides a 'os2forms_nemid_company_postal_code' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_postal_code",
 *   label = @Translation("NemID Company PostalCode"),
 *   description = @Translation("Provides a NemID Company PostalCode element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyPostalCode
 */
class NemidCompanyPostalCode extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CompanyLookupResult::POSTAL_CODE;
  }

}
