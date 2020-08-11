<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_city' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_city",
 *   label = @Translation("NemID Company City"),
 *   description = @Translation("Provides a NemID Company City element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyCity
 */
class NemidCompanyCity extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return 'company_city';
  }

}
