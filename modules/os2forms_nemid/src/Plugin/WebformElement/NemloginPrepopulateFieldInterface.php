<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Defines interface NemloginPopulateField.
 *
 * @package Drupal\os2forms_nemid\Plugin\WebformElement
 */
interface NemloginPrepopulateFieldInterface {

  /**
   * String representation of the Nemlogin field key.
   *
   * Is used to prepopulate the field from Nemlogin AuthProvider.
   *
   * @return string
   *   Field key.
   */
  public function getNemloginFieldKey();

}
