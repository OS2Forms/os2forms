<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

/**
 * Provides a 'os2forms_nemid_pid' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_pid",
 *   label = @Translation("NemID PID"),
 *   description = @Translation("Provides a NemID PID element."),
 *   category = @Translation("NemID"),
 * )
 *
 * @see \Drupal\os2forms_nemid\Plugin\NemidElementBase
 * @see \Drupal\os2forms_nemid\Element\NemidPid
 */
class NemidPid extends NemidElementBase implements NemidElementPersonalInterface {

  /**
   * {@inheritdoc}
   */
  public function getNemloginFieldKey() {
    return 'pid';
  }

}
