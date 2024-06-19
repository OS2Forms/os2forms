<?php

namespace Drupal\os2forms_dawa\Entity;

/**
 * Class DatafordelerMatrikula.
 *
 * Wrapper class for Datafordeler matrikula object that easies the matrikula property
 * access.
 */
class DatafordelerMatrikula {

  /**
   * Owner licence code / ejerlavskode.
   * @var string
   */
  protected string $ownerLicenseCode;

  /**
   * Ownership name / ejerlavsnavn.
   * @var string
   */
  protected string $ownershipName;


  /**
   * Matrikula number / matrikelnummer.
   * @var string
   */
  protected string $matrikulaNumber;

  /**
   * DawaAddress constructor.
   *
   * Fills the property from the provided JSON metadata.
   *
   * @param array $json
   *   Address properties as JSON metadata.
   */
  public function __construct(array $json) {
    if (isset($json['features']) && is_array($json['features'])) {
      $jordstykke = $json['features'][0]['properties']['jordstykke'][0];

      $this->ownerLicenseCode = $jordstykke['properties']['ejerlavskode'];
      $this->ownershipName = $jordstykke['properties']['ejerlavsnavn'];
      $this->matrikulaNumber = $jordstykke['properties']['matrikelnummer'];
    }
  }

  public function getOwnerLicenseCode(): string {
    return $this->ownerLicenseCode;
  }

  public function getOwnershipName(): string {
    return $this->ownershipName;
  }

  public function getMatrikulaNumber(): string {
    return $this->matrikulaNumber;
  }



}
