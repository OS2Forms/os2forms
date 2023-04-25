<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a 'os2forms_nemid_house_nr' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_house_nr",
 *   label = @Translation("NemID House nr"),
 *   description = @Translation("Provides a NemID House nr element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidHouseNr
 */
class NemidHouseNr extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CprLookupResult::HOUSE_NR;
  }

}
