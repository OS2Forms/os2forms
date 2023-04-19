<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\os2web_datalookup\LookupResult\CprLookupResult;

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
class NemidName extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return CprLookupResult::NAME;
  }

}
