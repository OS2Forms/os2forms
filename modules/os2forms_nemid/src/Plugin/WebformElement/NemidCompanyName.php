<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

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
class NemidCompanyName extends NemidElementBase implements NemidElementCompanyInterface {

  /**
   * {@inheritdoc}
   */
  public function getNemloginFieldKey() {
    // TODO: Implement getNemloginFieldKey() method.
  }

}
