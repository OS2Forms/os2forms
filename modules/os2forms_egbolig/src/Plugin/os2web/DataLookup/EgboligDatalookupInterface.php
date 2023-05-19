<?php

namespace Drupal\os2forms_egbolig\Plugin\os2web\DataLookup;

use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterface;

/**
 * EgboligInterface plugin interface.
 *
 * Provides functions for getting the plugin configuration values.
 *
 * @ingroup plugin_api
 */
interface EgboligDatalookupInterface extends DataLookupInterface {

  /**
   * Returns URL for the EGBolig webservice.
   *
   * @return string
   *   Autocomplete path used for address.
   */
  public function getWebserviceUrl();

  /**
   * Returns if EGBolig is running in TEST mode.
   *
   * That allows using test CPR instead of a real one.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isTestMode();

  /**
   * Returns test CPR of EGBolig.
   *
   * @return string
   *   Test CPR.
   */
  public function getTestCpr();

  /**
   * Fetches member information from webservice.
   *
   * @param string $cpr
   *   CPR of the member to fetch.
   *
   * @return mixed
   *   NULL if member no found, or Member object.
   */
  public function fetchMember($cpr);

  /**
   * Creates the member.
   *
   * Calls PaymentNewMember function of a webservice.
   *
   * @param mixed $member
   *   Member object.
   */
  public function createMember($member);

  /**
   * Fetches children information related with member.
   *
   * Calls ChildGetByMember function of a webservice.
   *
   * @param mixed $member
   *   Member object.
   *
   * @return mixed
   *   Array of children or empty array.
   */
  public function fetchChildren($member);

  /**
   * Returns room wishes information.
   *
   * Calls WishGetList function of a webservice.
   *
   * @param mixed $member
   *   Member object.
   *
   * @return array
   *   Returns min and max room from wish list as array
   *     [
   *       'minRoom' => minRoom,
   *       'maxRoom' => maxRoom
   *     ]
   *
   *   If values for minRoom or maxRoom could not be found, array with NULL
   *   values is returned.
   */
  public function fetchRoomWishes($member);

  /**
   * Fetches the institution name options form webservice.
   *
   * Calls EducationalInstitutionGetList function of a webservice.
   *
   * @return array
   *   Array of options.
   */
  public function fetchInstitutionNameOptions();

  /**
   * Fetches the education type options form webservice.
   *
   * Calls EducationGetList function of a webservice.
   *
   * @return array
   *   Array of options.
   */
  public function fetchEducationTypeOptions();

  /**
   * Activates the membership for the member.
   *
   * Calls MembershipActivateMembership function of webservice.
   *
   * If member is inactive (1 = i bero), it will be activated.
   * If member is already active (0) nothing will be done
   *
   * @param mixed $member
   *   Member object.
   */
  public function activateMembership($member);

  /**
   * Updates member via the webservice.
   *
   * Calls MemberUpdate, MemberUpdateCriterias, MemberUpdateEducation functions
   * of a webservice.
   *
   * @param mixed $member
   *   Member object.
   */
  public function updateMember($member);

  /**
   * Updates the partner.
   *
   * Calls to MemberUpdatePartner function of webservice.
   *
   * @param mixed $partner
   *   Partner object.
   */
  public function updatePartner($partner);

  /**
   * Adds a child to a member.
   *
   * Calls ChildDelete function of a webservice.
   *
   * @param mixed $child
   *   Child object.
   */
  public function addChild($child);

  /**
   * Deletes a child from a member.
   *
   * Calls ChildDelete function of a webservice.
   *
   * @param mixed $child
   *   Child object.
   */
  public function deleteChild($child);

  /**
   * Deletes all wishes from a member.
   *
   * Calls WishDeleteByMember function of a webservice.
   *
   * @param mixed $member
   *   Member object.
   */
  public function deleteWishes($member);

  /**
   * Fetches a list of the department based on give region number.
   *
   * Calls DepartmentGetListByRegion function of a webservice.
   *
   * @param int $regionNo
   *   Number of a region 1, 2, 3 or 4.
   *
   * @return array
   *   List of the departments.
   */
  public function fetchDepartments($regionNo);

  /**
   * Adds wishes.
   *
   * Calls WishAddList function of a webservice.
   *
   * @param array $wishes
   *   List of wishes.
   */
  public function addWishList(array $wishes);

}
