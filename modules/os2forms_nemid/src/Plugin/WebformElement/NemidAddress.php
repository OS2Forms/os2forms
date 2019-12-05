<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_address' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_address",
 *   label = @Translation("NemID Address"),
 *   description = @Translation("Provides a NemID Address element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidAddress
 */
class NemidAddress extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return 'address';
  }

}
