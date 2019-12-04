<?php

namespace Drupal\os2forms_dawa\Plugin\os2web\DataLookup;

use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterface;

/**
 * DawaDataLookupInterface plugin interface.
 *
 * Provides functions for getting the plugin configuration values.
 *
 * @ingroup plugin_api
 */
interface DawaDataLookupInterface extends DataLookupInterface {

  /**
   * Returns path for address autocomplete field.
   *
   * @return string
   *   Autocomplete path used for address.
   */
  public function getAddressAutocompletePath();

  /**
   * Returns path for fetching addresses.
   *
   * @return string
   *   Path used for address API.
   */
  public function getAddressApiPath();

  /**
   * Returns path for block autocomplete field.
   *
   * @return string
   *   Autocomplete path used for address.
   */
  public function getBlockAutocompletePath();

  /**
   * Returns path for matrikula autocomplete field.
   *
   * @return string
   *   Autocomplete path used for address.
   */
  public function getMatrikulaAutocompletePath();

}
