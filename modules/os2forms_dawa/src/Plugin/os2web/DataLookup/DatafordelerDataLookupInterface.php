<?php

namespace Drupal\os2forms_dawa\Plugin\os2web\DataLookup;

use Drupal\os2forms_dawa\Entity\DatafordelerMatrikula;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterface;

/**
 * DatafordelerDataLookupInterface plugin interface.
 *
 * Provides functions for getting the plugin configuration values.
 *
 * @ingroup plugin_api
 */
interface DatafordelerDataLookupInterface extends DataLookupInterface {

  /**
   * Returns list of ID for Matrikula / jordstykke that is related with this address.
   *
   * @param string $addressAccessId
   *   Address to make search against.
   *
   * @return array
   *   List if IDs.
   */
  public function getMatrikulaIds(string $addressAccessId) : array;

  /**
   * Returns matrikule entry that is found byt this ID.
   *
   * @param string $matrikulaId
   *   Id to make search  against.
   *
   * @return DatafordelerMatrikula|NULL
   *   Matrikula entry or NULL.
   */
  public function getMatrikulaEntry(string $matrikulaId) : ?DatafordelerMatrikula;
}
