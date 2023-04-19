<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;

/**
 * Provides a 'os2forms_nemid_company_name' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_name",
 *   label = @Translation("NemID Company Name"),
 *   description = @Translation("Provides a NemID Company Name element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyName
 */
class NemidCompanyName extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CompanyLookupResult::NAME;
  }

}
