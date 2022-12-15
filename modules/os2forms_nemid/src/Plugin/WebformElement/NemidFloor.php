<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a 'os2forms_nemid_floor' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_floor",
 *   label = @Translation("NemID Floor"),
 *   description = @Translation("Provides a NemID Floor element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidFloor
 */
class NemidFloor extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return CprLookupResult::FLOOR;
  }

}
