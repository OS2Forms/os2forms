<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_coaddress' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_coaddress",
 *   label = @Translation("NemID Coaddress"),
 *   description = @Translation("Provides a NemID Coaddress element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCoaddress
 */
class NemidCoaddress extends NemidElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getNemloginFieldKey() {
    // TODO: Implement getNemloginFieldKey() method.
  }

}
