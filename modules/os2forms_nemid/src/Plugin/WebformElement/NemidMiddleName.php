<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

/**
 * Provides a 'os2forms_nemid_middle_name' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_middle_name",
 *   label = @Translation("NemID Middle name"),
 *   description = @Translation("Provides a NemID Middle name element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidMiddleName
 */
class NemidMiddleName extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CprLookupResult::MIDDLE_NAME;
  }

}
