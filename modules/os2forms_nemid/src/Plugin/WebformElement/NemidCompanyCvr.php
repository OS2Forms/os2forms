<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_company_cvr' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_cvr",
 *   label = @Translation("NemID Company CVR"),
 *   description = @Translation("Provides a NemID Company CVR element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyCvr
 */
class NemidCompanyCvr extends ServiceplatformenCompanyElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return 'cvr';
  }

}
