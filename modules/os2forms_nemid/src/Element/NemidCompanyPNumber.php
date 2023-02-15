<?php

namespace Drupal\os2forms_nemid\Element;

/**
 * Provides a 'os2forms_nemid_company_p_number'.
 *
 * @FormElement("os2forms_nemid_company_p_number")
 */
class NemidCompanyPNumber extends CompositeFetchDataBase {

  /**
   * {@inheritdoc}
   */
  public static function getFormElementId() {
    return 'os2forms_nemid_company_p_number';
  }

  /**
   * {@inheritdoc}
   */
  public static function getValueElementName() {
    return 'p_number_value';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubmitElementName() {
    return 'p_number_submit';
  }

}
