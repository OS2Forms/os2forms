<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a 'os2forms_nemid_city' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_city",
 *   label = @Translation("NemID City"),
 *   description = @Translation("Provides a NemID City element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCity
 */
class NemidCity extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CprLookupResult::CITY;
  }

}
