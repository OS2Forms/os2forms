<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_name' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_name",
 *   label = @Translation("NemID Name"),
 *   description = @Translation("Provides a NemID Name element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidName
 */
class NemidName extends NemidElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getNemloginFieldKey() {
    // TODO: Implement getNemloginFieldKey() method.
  }

}
