<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;

/**
 * Provides a 'os2forms_nemid_company_kommunekode' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_kommunekode",
 *   label = @Translation("NemID Company Kommunekode"),
 *   description = @Translation("Provides a NemID Company Kommunekode element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyKommunekode
 */
class NemidCompanyKommunekode extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CompanyLookupResult::MUNICIPALITY_CODE;
  }

}
