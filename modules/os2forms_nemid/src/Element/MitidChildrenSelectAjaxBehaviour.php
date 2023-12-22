<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a children select ajax behaviour for MitidChildrenSelect and MitidChildrenRadios.
 */
class MitidChildrenSelectAjaxBehaviour {

  public static function mitidChildrenSelectAjax(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $triggerName = $trigger['#name'];

    $childCpr = $form_state->getValue($triggerName);

    /** @var \Drupal\os2web_datalookup\Plugin\DataLookupManager $os2web_datalookup_plugins */
    $os2web_datalookup_plugins = \Drupal::service('plugin.manager.os2web_datalookup');

    /** @var \Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCpr $cprPlugin */
    $cprPlugin = $os2web_datalookup_plugins->createDefaultInstanceByGroup('cpr_lookup');

    if ($cprPlugin->isReady()) {
      $cprLookupResult = $cprPlugin->lookup($childCpr);
    }

    /** @var \Drupal\webform\WebformSubmissionForm $webformSubmissionForm */
    $webformSubmissionForm = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
    $webformSubmission = $webformSubmissionForm->getEntity();
    $webform = $webformSubmission->getWebform();
    $elements = $webform->getElementsDecodedAndFlattened();

    $response = new AjaxResponse();

    foreach ($elements as $element) {
      if(isset($element['#type'])) {
        switch ($element['#type']) {
          case 'os2forms_mitid_child_name':
            $element['#value'] = $cprLookupResult->getName();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-name', $element));
            break;
          case 'os2forms_mitid_child_cpr':
            $element['#value'] = $cprLookupResult->getCpr();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-cpr', $element));
            break;
          case 'os2forms_mitid_child_address':
            $element['#value'] = $cprLookupResult->getAddress();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-address', $element));
            break;
          case 'os2forms_mitid_child_apartment_nr':
            $element['#value'] = $cprLookupResult->getApartmentNr();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-apartment_nr', $element));
            break;
          case 'os2forms_mitid_child_city':
            $element['#value'] = $cprLookupResult->getCity();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-city', $element));
            break;
          case 'os2forms_mitid_child_coaddress':
            $element['#value'] = $cprLookupResult->getCoName();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-coaddress', $element));
            break;
          case 'os2forms_mitid_child_floor':
            $element['#value'] = $cprLookupResult->getFloor();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-floor', $element));
            break;
          case 'os2forms_mitid_child_house_nr':
            $element['#value'] = $cprLookupResult->getHouseNr();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-house-nr', $element));
            break;
          case 'os2forms_mitid_child_kommunekode':
            $element['#value'] = $cprLookupResult->getMunicipalityCode();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-kommunekode', $element));
            break;
          case 'os2forms_mitid_child_postal_code':
            $element['#value'] = $cprLookupResult->getPostalCode();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-postal-code', $element));
            break;
          case 'os2forms_mitid_child_street':
            $element['#value'] = $cprLookupResult->getStreet();
            $response->addCommand(new ReplaceCommand('.js-form-type-os2forms-mitid-child-street', $element));
            break;
        }
      }
    }

    return $response;
  }
}
