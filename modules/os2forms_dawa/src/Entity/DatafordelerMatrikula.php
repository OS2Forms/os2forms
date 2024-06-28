<?php

namespace Drupal\os2forms_dawa\Entity;

/**
 * Class DatafordelerMatrikula.
 *
 * Wrapper class for Datafordeler matrikula object that easies
 * the matrikula property access.
 */
class DatafordelerMatrikula {

  /**
   * Owner licence code / ejerlavskode.
   *
   * @var string
   */
  protected string $ownerLicenseCode;

  /**
   * Ownership name / ejerlavsnavn.
   *
   * @var string
   */
  protected string $ownershipName;


  /**
   * Matrikula number / matrikelnummer.
   *
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
    $this->ownerLicenseCode = $json['properties']['ejerlavskode'];
    $this->ownershipName = $json['properties']['ejerlavsnavn'];
    $this->matrikulaNumber = $json['properties']['matrikelnummer'];
  }

  /**
   * Returns owner licence code.
   *
   * @return string
   *   Owners licence code.
   */
  public function getOwnerLicenseCode(): string {
    return $this->ownerLicenseCode;
  }

  /**
   * Returns ownership name.
   *
   * @return string
   *   ownership name.
   */
  public function getOwnershipName(): string {
    return $this->ownershipName;
  }

  /**
   * Returns makrikula number.
   *
   * @return string
   *   Matrikula number
   */
  public function getMatrikulaNumber(): string {
    return $this->matrikulaNumber;
  }

}
