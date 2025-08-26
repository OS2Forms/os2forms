<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms_nemid\Service\FormsHelper;

/**
 * Provides a children select ajax behaviour.
 *
 * User in MitidChildrenSelect and MitidChildrenRadios.
 */
class MitidChildrenSelectAjaxBehaviour {

  /**
   * Ajax function to update fields after child select.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function mitidChildrenSelectAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Getting child CPR.
    $trigger = $form_state->getTriggeringElement();
    $triggerName = $trigger['#name'];
    $childCpr = $form_state->getValue($triggerName);

    /** @var \Drupal\os2web_datalookup\Plugin\DataLookupManager $os2web_datalookup_plugins */
    $os2web_datalookup_plugins = \Drupal::service('plugin.manager.os2web_datalookup');

    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCprInterface $cprPlugin */
    $cprPlugin = $os2web_datalookup_plugins->createDefaultInstanceByGroup('cpr_lookup');

    if ($cprPlugin->isReady()) {
      $cprLookupResult = $cprPlugin->lookup($childCpr);

      if (!$cprLookupResult->isSuccessful()) {
        return $response;
      }

      /** @var \Drupal\webform\WebformSubmissionForm $webformSubmissionForm */
      $webformSubmissionForm = $form_state->getFormObject();
      /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
      $webformSubmission = $webformSubmissionForm->getEntity();
      $webform = $webformSubmission->getWebform();
      $elementsFlattened = $webform->getElementsInitializedAndFlattened();

      foreach ($elementsFlattened as $flattenedElement) {
        if (isset($flattenedElement['#type'])) {
          $parents = $flattenedElement['#webform_parents'];
          $element = NestedArray::getValue($form['elements'], $parents);

          switch ($element['#type']) {
            case 'os2forms_mitid_child_name':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getName() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-name', $element));
              break;

            case 'os2forms_mitid_child_cpr':
              $element['#value'] = $cprLookupResult->getCpr();
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-cpr', $element));
              break;

            case 'os2forms_mitid_child_address':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getAddress() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-address', $element));
              break;

            case 'os2forms_mitid_child_apartment_nr':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getApartmentNr() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-apartment-nr', $element));
              break;

            case 'os2forms_mitid_child_city':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getCity() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-city', $element));
              break;

            case 'os2forms_mitid_child_coaddress':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getCoName() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-coaddress', $element));
              break;

            case 'os2forms_mitid_child_floor':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getFloor() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-floor', $element));
              break;

            case 'os2forms_mitid_child_house_nr':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getHouseNr() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-house-nr', $element));
              break;

            case 'os2forms_mitid_child_kommunekode':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getMunicipalityCode() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-kommunekode', $element));
              break;

            case 'os2forms_mitid_child_postal_code':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getPostalCode() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-postal-code', $element));
              break;

            case 'os2forms_mitid_child_street':
              $element['#value'] = !$cprLookupResult->isNameAddressProtected() ? $cprLookupResult->getStreet() : t('Name and address protected');
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-street', $element));
              break;

            case 'os2forms_mitid_child_other_guardian':
              $otherGuardianCpr = NULL;
              $allGuardians = $cprLookupResult->getGuardians();

              if (!empty($allGuardians)) {
                // Making a guess primary CPR is saved in the form state.
                $primaryCprLookupResult = FormsHelper::retrieveCachedCprLookupResult($form_state);

                if ($primaryCprLookupResult) {
                  foreach ($allGuardians as $guardian) {
                    // Find other CPR number.
                    if ($guardian['cpr'] != $primaryCprLookupResult->getCpr()) {
                      $otherGuardianCpr = $guardian['cpr'];
                    }
                  }
                }
              }

              $element['#value'] = $otherGuardianCpr;
              $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-other-guardian', $element));
              break;
          }
        }
      }
    }

    return $response;
  }

}
