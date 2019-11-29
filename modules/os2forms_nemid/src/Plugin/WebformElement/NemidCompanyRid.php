<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_company_rid' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_company_rid",
 *   label = @Translation("NemID Company RID"),
 *   description = @Translation("Provides a NemID Company RID element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCompanyRid
 */
class NemidCompanyRid extends NemidElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getNemloginFieldKey() {
    return 'rid';
  }

}
