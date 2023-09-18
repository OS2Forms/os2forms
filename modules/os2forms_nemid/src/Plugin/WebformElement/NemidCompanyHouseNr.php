<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;

/**
 * Provides a 'os2forms_nemid_company_house_nr' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_house_nr",
 *   label = @Translation("NemID Company HouseNr"),
 *   description = @Translation("Provides a NemID Company HouseNr element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyHouseNr
 */
class NemidCompanyHouseNr extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CompanyLookupResult::HOUSE_NR;
  }

}
