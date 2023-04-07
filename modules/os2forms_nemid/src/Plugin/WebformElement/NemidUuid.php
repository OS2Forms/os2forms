<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_uuid' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_uuid",
 *   label = @Translation("NemID UUID"),
 *   description = @Translation("Provides a NemID UUID element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidUuid
 */
class NemidUuid extends NemloginElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getPrepopulateFieldFieldKey(array &$element) {
    return 'uuid';
  }

}
