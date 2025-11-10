<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines interface NemloginPopulateField.
 *
 * @package Drupal\os2forms_nemid\Plugin\WebformElement
 */
interface NemidPrepopulateFieldInterface {

  /**
   * String representation of the prepopulate field key.
   *
   * Is used to prepopulate the field from the corresponding plugin.
   *
   * @param array $element
   *   The element to prepopulate value for.
   *
   * @return string
   *   Field key.
   */
  public function getPrepopulateFieldFieldKey(array &$element);

  /**
   * Prepopulating of the field on webform load.
   *
   * @param array $element
   *   The element to prepopulate value for.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object, in case we need to access properties or use it for
   *   temporary storage.
   */
  public function handleElementPrepopulate(array &$element, FormStateInterface &$form_state);

}
