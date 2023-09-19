<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;

/**
 * Provides a 'os2forms_nemid_company_street' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_street",
 *   label = @Translation("NemID Company Street"),
 *   description = @Translation("Provides a NemID Company Street element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyStreet
 */
class NemidCompanyStreet extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CompanyLookupResult::STREET;
  }

}
