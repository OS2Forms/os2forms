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
   * Latitude of the address.
   *
   * @var float
   */
  protected $latitude;

  /**
   * Longitude of the address.
   *
   * @var float
   */
  protected $longitude;

  /**
   * Address access ID.
   *
   * @var string
   */
  protected $accessAddressId;

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
      $this->longitude = $json['adgangsadresse']['adgangspunkt']['koordinater'][0];
      $this->latitude = $json['adgangsadresse']['adgangspunkt']['koordinater'][1];
      $this->accessAddressId = $json['adgangsadresse']['id'];
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

  /**
   * Gets latitude.
   *
   * @return float
   *   property latitude.
   */
  public function getLatitude() {
    return $this->latitude;
  }

  /**
   * Gets longitude.
   *
   * @return float
   *   property longitude.
   */
  public function getLongitude() {
    return $this->longitude;
  }

  /**
   * Gets Address access ID.
   *
   * @return string
   *   Address access ID.
   */
  public function getAccessAddressId() {
    return $this->accessAddressId;
  }

}
