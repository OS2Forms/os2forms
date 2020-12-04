<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_kommunekode' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_kommunekode",
 *   label = @Translation("NemID Kommunekode"),
 *   description = @Translation("Provides a NemID Kommunekode element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidKommunekode
 */
class NemidKommunekode extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return 'kommunekode';
  }

}
