<?php

/**
 * @file
 * OS2Forms Egbolig utils file.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Sets the default value of the field.
 *
 * By using the element parents this function works fine with for both flat or
 * nested webform structure.
 *
 * @param array $form
 *   Submission form.
 * @param \Drupal\webform\WebformInterface $webform
 *   The original webform of the submission.
 * @param string $element_key
 *   Key of the element that needs default value updated.
 * @param string $value
 *   The value of the element to be set.
 */
function os2forms_egbolig_set_field_default_value(array &$form, WebformInterface $webform, $element_key, $value) {
  $webform_element = $webform->getElement($element_key);

  $field = NestedArray::getValue($form['elements'], $webform_element['#webform_parents']);
  $field['#default_value'] = $value;

  NestedArray::setValue($form['elements'], $webform_element['#webform_parents'], $field);
}

/**
 * Populates the member object with the values from the form submission.
 *
 * Note: $member param is nullable. In this case a new member class instance is
 * created with the default settings.
 *
 * @param mixed $member
 *   Member object.
 *   Can be NULL, if NULL new member class instance is created with the default
 *   settings.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Current state of the form.
 *
 * @return object
 *   Populated member object.
 */
function os2forms_egbolig_populate_member($member, FormStateInterface $form_state) {
  $values = $form_state->getValues();

  // Check if member is empty, create a new member and populate some default
  // fields.
  if ($member == NULL) {
    $member = new stdClass();
    $member->Password = OS2FORMS_EGBOLIG_MEMBER_DEFAULT_PASSWORD;
    $member->OfferByEmail = OS2FORMS_EGBOLIG_MEMBER_DEFAULT_OFFER_BY_EMAIL;
    $member->MemberNo = $values['cpr'];
  }
  $member->Email = $values['email'];
  $member->Name = $values['name'];
  $member->Address = $values['address'];
  $member->HomePhone = $values['phone_nr'];
  $member->CoAddress = $values['c_o_address'];
  $member->MobilePhone = $values['phone_mobile'];
  $member->PostalCodeCity = $values['city_zip'];
  $member->Country = OS2FORMS_EGBOLIG_MEMBER_DEFAULT_COUNTRY;
  // Count od adults with partner.
  $member->Adults = 1 + $values['has_partner'];

  $member->Children = $values['has_children'];

  $cpr = $values['cpr'];
  $cpr62 = substr($cpr, 0, 6);
  $cpr42 = substr($cpr, -4);
  $member->cpr42 = $cpr42;
  $member->cpr62 = $cpr62;

  $member->Education = !empty($values['education_type']) ? $values['education_type'] : NULL;
  $member->EducationStart = !empty($values['education_start_date']) ? date("Y-m-d", strtotime(str_replace('/', '.', $values['education_start_date']))) : NULL;
  $member->EducationEnd = !empty($values['education_end_date']) ? date("Y-m-d", strtotime(str_replace('/', '.', $values['education_end_date']))) : NULL;
  $member->EducationExpectedEnd = $member->EducationEnd;
  $member->EducationDocumentationReceived = empty($values['education_doc']) ? FALSE : TRUE;

  $member->EducationalInstitution = !empty($values['institution_name']) ? $values['institution_name'] : NULL;
  if ($values['husleje_min']) {
    $member->MinimumRent = $values['husleje_min'];
  }
  $member->MaximumRent = $values['husleje_max'];
  $member->Pet = $values['husdyr'];
  $member->Criteria1 = $values['job_status'];
  if ($member->Criteria1 == 11) {
    $member->Criteria2 = $values['arsog'];
  }

  return $member;
}

/**
 * Populates the partner object with the values from the form submission.
 *
 * @param mixed $member
 *   Member object.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Current state of the form.
 *
 * @return object
 *   Populated partner object.
 */
function os2forms_egbolig_populate_partner($member, FormStateInterface $form_state) {
  $values = $form_state->getValues();

  // Update partner.
  if ((int) $values['has_partner']) {
    $partner_cpr = $values['partner_cpr'];
    $partner_cpr62 = substr($partner_cpr, 0, 6);
    $partner_cpr42 = substr($partner_cpr, -4);

    $cprInfo = \Drupal::service('plugin.manager.os2web_datalookup')->createInstance('serviceplatformen_cpr')->getAddress($partner_cpr);
    $partner_name = $cprInfo['name'];

    $partner = new stdClass();
    $partner->memberCompanyNo = $member->MemberCompanyNo;
    $partner->memberNo = $member->MemberNo;
    $partner->name = $partner_name;
    $partner->cpr62 = $partner_cpr62;
    $partner->cpr42 = $partner_cpr42;
  }
  // Remove partner.
  else {
    $partner = new stdClass();
    $partner->memberCompanyNo = $member->MemberCompanyNo;
    $partner->memberNo = $member->MemberNo;
    $partner->name = '';
  }

  return $partner;
}

/**
 * Does the sync of the member children.
 *
 * Takes care of adding new children, or deleting the old ones.
 *
 * @param mixed $member
 *   Member object.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Current state of the form.
 */
function os2forms_egbolig_sync_children($member, FormStateInterface $form_state) {
  $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');

  /** @var \Drupal\os2forms_egbolig\Plugin\os2web\DataLookup\EgboligDatalookupInterface $egboligClient */
  $egboligClient = $pluginManager->createInstance('egbolig');

  $values = $form_state->getValues();
  $existingChildren = $egboligClient->fetchChildren($member);

  /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCPR $cprService */
  $cprService = \Drupal::service('plugin.manager.os2web_datalookup')->createInstance('serviceplatformen_cpr');

  for ($i = 1; $i <= 10; $i++) {
    $child_cpr = $values["child_cpr_nr_$i"];

    if (!empty($child_cpr)) {
      // Adding child if child is not in the list already.
      if (!_os2forms_egbolig_child_exists($child_cpr, $existingChildren)) {
        // Getting children name.
        $cprInfo = $cprService->getAddress($child_cpr);
        $child_name = $cprInfo['name'];

        $child = new stdClass();
        $child->MemberNo = $member->MemberNo;
        $child->CompanyNo = $member->MemberCompanyNo;
        $child->Name = $child_name;
        $child->Cpr4 = substr($child_cpr, -4);
        $child->Cpr6 = substr($child_cpr, 0, 6);

        $egboligClient->addChild($child);
      }

      _os2forms_egbolig_remove_child_from_list($child_cpr, $existingChildren);
    }
  }

  // Delete children which are no longer added to the membership.
  foreach ($existingChildren as $child) {
    $child->MemberNo = $member->MemberNo;
    $child->CompanyNo = $member->MemberCompanyNo;
    $child->Name = $child['Name'];
    $child->Cpr4 = $child['Cpr4'];
    $child->Cpr6 = $child['Cpr6'];

    $egboligClient->deleteChild($child);
  }
}

/**
 * Checks if child exists in the list.
 *
 * @param string $child_cpr
 *   The CPR of the child to search for.
 * @param array $childrenList
 *   Array of existing children.
 *
 * @return bool
 *   TRUE or FALSE.
 */
function _os2forms_egbolig_child_exists($child_cpr, array $childrenList) {
  foreach ($childrenList as $child) {
    if ($child['Cpr4'] == substr($child_cpr, -4) && $child['Cpr6'] == substr($child_cpr, 0, 6)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Removes child from children list.
 *
 * @param string $child_cpr
 *   The CPR of the child to to remove from the list.
 * @param array $childrenList
 *   Children list.
 */
function _os2forms_egbolig_remove_child_from_list($child_cpr, array &$childrenList) {
  for ($i = 0; $i <= count($childrenList); $i++) {
    if ($childrenList[$i]['Cpr4'] == substr($child_cpr, -4) && $childrenList[$i]['Cpr6'] == substr($child_cpr, 0, 6)) {
      unset($childrenList[$i]);
      break;
    }
  }

  reset($childrenList);
}

/**
 * Does sync of member wishes.
 *
 * Takes care of adding new wishes, and deleting the old ones.
 *
 * @param mixed $member
 *   Member object.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Current state of the form.
 */
function os2forms_egbolig_sync_wishes($member, FormStateInterface $form_state) {
  $pluginManager = \Drupal::service('plugin.manager.os2web_datalookup');

  /** @var \Drupal\os2forms_egbolig\Plugin\os2web\DataLookup\EgboligDatalookupInterface $egboligClient */
  $egboligClient = $pluginManager->createInstance('egbolig');

  // Deleting all wishes first.
  $egboligClient->deleteWishes($member);

  $values = $form_state->getValues();

  $minRoom = intval($values['antal_rum_min']);
  $maxRoom = intval($values['antal_rum_max']);

  $wishes = [];
  $submittedRegions = [];
  for ($i = 1; $i <= 4; $i++) {
    // Get region IDs.
    $regionNo = $values["priority_$i"];
    if (!empty($regionNo)) {

      // Skipping duplicates.
      if (!in_array($regionNo, $submittedRegions)) {
        $submittedRegions[] = $regionNo;
      }
      else {
        continue;
      }

      $departments = $egboligClient->fetchDepartments($regionNo);

      // Composing list of wishes.
      foreach ($departments as $key => $department) {
        for ($j = $minRoom; $j <= $maxRoom; $j++) {
          $wishes[] = [
            'CompanyNo' => $department->CompanyNo,
            'MemberCompanyNo' => $member->MemberCompanyNo,
            'DepartmentNo' => $department->DepartmentNo,
            'Room' => $j,
            'FloorMax' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_MAX_FLOOR,
            'FloorMin' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_MIN_FLOOR,
            'SqmMax' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_MAX_SQM,
            'SqmMin' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_MIN_SQM,
            'MemberNo' => $member->MemberNo,
            'TenancyType' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_TENANCY_TYPE,
          ];
        }
      }
    }
  }

  // Submitting list of wishes.
  $egboligClient->addWishList($wishes);
}
