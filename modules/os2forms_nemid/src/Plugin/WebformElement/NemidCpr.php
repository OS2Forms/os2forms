<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_cpr' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_cpr",
 *   label = @Translation("NemID CPR"),
 *   description = @Translation("Provides a NemID CPR element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidCpr
 */
class NemidCpr extends ServiceplatformenCprElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey() {
    return 'cpr';
  }

}
