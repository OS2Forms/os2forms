<?php

namespace Drupal\os2forms_dawa\Entity;

/**
 * Class DawaAddress.
 *
 * Wrapper class for DAWA address object that easies the address property
 * access.
 */
class DawaAddress {

  /**
   * ID of the address.
   *
   * @var string
   */
  protected $id;

  /**
   * Municipality code of the address.
   *
   * @var string
   */
  protected $municipalityCode;

  /**
   * Property number of the address.
   *
   * @var string
   */
  protected $propertyNumber;

  /**
   * DawaAddress constructor.
   *
   * Fills the property from the provided JSON metadata.
   *
   * @param array $json
   *   Address properties as JSON metadata.
   */
  public function __construct(array $json) {
    $this->id = $json['id'];

    if (isset($json['adgangsadresse']) && is_array($json['adgangsadresse'])) {
      $this->municipalityCode = $json['adgangsadresse']['kommune']['kode'];
      $this->propertyNumber = $json['adgangsadresse']['esrejendomsnr'];
    }
  }

  /**
   * Gets address ID.
   *
   * @return string
   *   ID of the address.
   */
  public function id() {
    return $this->id;
  }

  /**
   * Gets municipality code.
   *
   * @return string
   *   Municipality code of the address.
   */
  public function getMunicipalityCode() {
    return $this->municipalityCode;
  }

  /**
   * Gets property number.
   *
   * @return string
   *   property number of the address.
   */
  public function getPropertyNumber() {
    return $this->propertyNumber;
  }

}
