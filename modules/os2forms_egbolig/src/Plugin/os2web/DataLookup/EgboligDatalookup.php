<?php

namespace Drupal\os2forms_egbolig\Plugin\os2web\DataLookup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupBase;
use SoapClient;
use Exception;

/**
 * Defines a plugin for Dawa Data.
 *
 * @DataLookup(
 *   id = "egbolig",
 *   label = @Translation("EGBolig webservice"),
 * )
 */
class EgboligDatalookup extends DataLookupBase implements EgboligDatalookupInterface {

  /**
   * Soap Client.
   *
   * @var \SoapClient
   */
  private $client;

  /**
   * {@inheritdoc}
   */
  public function getWebserviceUrl() {
    return $this->configuration['egbolig_webservice_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function isTestMode() {
    return $this->configuration['egbolig_webservice_test_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestCpr() {
    return $this->configuration['egbolig_webservice_test_cpr'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'egbolig_webservice_url' => 'http://egboligws.ballerup.dk/services/service10632.svc?wsdl',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['egbolig_webservice_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('EGBolig webservice URL'),
      '#default_value' => $this->configuration['egbolig_webservice_url'],
      '#required' => TRUE,
      '#description' => $this->t('URL to EGBolig webservice, e.g. http://egboligws.ballerup.dk/services/service10632.svc?wsdl'),
    ];

    $form['egbolig_webservice_test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run EGBolig in TEST mode'),
      '#default_value' => $this->configuration['egbolig_webservice_test_mode'],
      '#description' => $this->t('If selected, the EGBolig will run in TEST mode, allowing to use test CPR instead of a real one'),
    ];

    $form['egbolig_webservice_test_cpr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test CPR'),
      '#default_value' => $this->configuration['egbolig_webservice_test_cpr'],
      '#description' => $this->t('Test CPR will be used instead of the real one. Useful for testing different scenarios'),
      '#states' => [
        'visible' => [
          [
            ':input[name="egbolig_webservice_test_mode"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $webserviceUrl = $form_state->getValue('egbolig_webservice_url');

    try {
      new SoapClient($webserviceUrl);
    }
    catch (Exception $e) {
      $form_state->setErrorByName('egbolig_webservice_url', t('Cannot initialize SOAP client: @message', ['@message' => $e->getMessage()]));
      \Drupal::logger('OS2Forms EGBolig')->error(t('Cannot initialize SOAP client: @message', ['@message' => $e->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['egbolig_webservice_url'] = $form_state->getValue('egbolig_webservice_url');
    $configuration['egbolig_webservice_test_mode'] = $form_state->getValue('egbolig_webservice_test_mode');
    $configuration['egbolig_webservice_test_cpr'] = $form_state->getValue('egbolig_webservice_test_cpr');
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    if ($this->client instanceof SoapClient) {
      return 'Plugin is ready to work';
    }
    else {
      $this->isReady = FALSE;
      return 'Client cannot be initialized. Check log messages';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    try {
      // Check if SOAP is available.
      $http = \Drupal::httpClient();
      $response = $http->get($this->getWebserviceUrl(), ['connect_timeout' => 2]);

      if ($response->getStatusCode() == 200) {
        $this->client = new SoapClient($this->getWebserviceUrl());
        $this->isReady = TRUE;
      }
    }
    catch (Exception $e) {
      $this->isReady = FALSE;
      \Drupal::logger('OS2Forms EGBolig')->error(t('Cannot initialize SOAP client: @message', ['@message' => $e->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchMember($cpr) {
    $response = $this->client->MemberGetListByCprNo(
      [
        'cprNo' => $cpr,
      ]
    );

    $result = $response->MemberGetListByCprNoResult;

    if (!empty((array) $result)) {
      if (isset($result->Member->Cpr42)) {
        // Formatting so that number is always 4 digits long.
        $result->Member->Cpr42 = sprintf("%'.04d", $result->Member->Cpr42);
      }
      if (isset($result->Member->Cpr62)) {
        // Formatting so that number is always 6 digits long.
        $result->Member->Cpr62 = sprintf("%'.06d", $result->Member->Cpr62);
      }

      return $result->Member;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createMember($member) {
    $member->Type = OS2FORMS_EGBOLIG_MEMBER_DEFAULT_TYPE_CREATE;

    $this->client->PaymentNewMember(
      [
        'paymentDetails' => [
          'NewAmounts' => [
            'Payment.NewAmount' => [
              'CompanyNo' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_COMPANY_NO,
              'CompanyNoToPayIn' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_COMPANY_NO_PAY_IN,
              'NoOfYears' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_NO_OF_YEARS,
              'Status' => OS2FORMS_EGBOLIG_MEMBER_DEFAULT_STATUS,
              'TenancyTypes' => [OS2FORMS_EGBOLIG_MEMBER_DEFAULT_TENANCY_TYPE],
            ],
          ],
        ],
        'member' => $member,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetchChildren($member) {
    $childrenList = [];

    $childrenResult = $this->client->ChildGetByMember([
      'companyNo' => $member->MemberCompanyNo,
      'memberNo' => $member->MemberNo,
    ])->ChildGetByMemberResult;

    if (isset($childrenResult->Child)) {
      $children = $childrenResult->Child;

      if (is_array($children)) {
        foreach ($children as $child) {
          $childrenList[] = [
            'AutoNo' => $child->AutoNo,
            // Formatting so that number is always 6 digits long.
            'Cpr6' => sprintf("%'.06d", $child->Cpr6),
            // Formatting so that number is always 4 digits long.
            'Cpr4' => sprintf("%'.04d", $child->Cpr4),
            'Name' => $child->Name,
          ];
        }
      }
      else {
        if (isset($children->AutoNo)) {
          $childrenList[] = [
            'AutoNo' => $children->AutoNo,
            // Formatting so that number is always 6 digits long.
            'Cpr6' => sprintf("%'.06d", $children->Cpr6),
            // Formatting so that number is always 4 digits long.
            'Cpr4' => sprintf("%'.04d", $children->Cpr4),
            'Name' => $children->Name,
          ];
        }
      }
    }

    return $childrenList;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRoomWishes($member) {
    $roomWishes = [
      'minRoom' => NULL,
      'maxRoom' => NULL,
    ];

    $wishList = $this->client->WishGetList(
      [
        'memberCompanyNo' => $member->MemberCompanyNo,
        'memberNo' => $member->MemberNo,
      ]
    );

    $result = $wishList->WishGetListResult;
    if (!empty($result)) {
      $wishes = $result->Wish;

      if (!empty($wishes)) {
        foreach ($wishes as $wish) {
          // Finding minimum minRoom.
          if (is_null($roomWishes['minRoom']) || $wish->Room < $roomWishes['minRoom']) {
            $roomWishes['minRoom'] = $wish->Room;
          }

          // Finding maximum maxRoom.
          if (is_null($roomWishes['maxRoom']) || $wish->Room > $roomWishes['maxRoom']) {
            $roomWishes['maxRoom'] = $wish->Room;
          }
        }
      }
    }

    return $roomWishes;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchInstitutionNameOptions() {
    $options = [];

    $institutions = $this->client->EducationalInstitutionGetList()->EducationalInstitutionGetListResult->EducationalInstitution;
    if (count($institutions) > 0) {
      foreach ($institutions as $inst) {
        $options[$inst->Id] = $inst->Name;
      }
    }
    else {
      $options[] = 'Ingen uddannelsessteder';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchEducationTypeOptions() {
    $options = [];

    $educations = $this->client->EducationGetList()->EducationGetListResult->Education;
    if (count($educations) > 0) {
      foreach ($educations as $education) {
        if (!in_array($education->Id, [1, 2, 3])) {
          continue;
        }
        elseif (strlen($education->Name) == 1) {
          continue;
        }
        elseif ($education->Name == 'æ' || $education->Name == 'ø' || $education->Name == 'å') {
          continue;
        }
        else {
          $options[$education->Id] = mb_convert_case($education->Name, MB_CASE_TITLE, "UTF-8");
        }
      }
    }
    else {
      $options[] = 'Ingen uddannelsestyper';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function activateMembership($member) {
    // Finding membership.
    $response = $this->client->MembershipGetList(
      [
        'memberCompanyNo' => $member->MemberCompanyNo,
        'memberNo' => $member->MemberNo,
      ]
    );
    $result = $response->MembershipGetListResult;

    if ($result) {
      // Activating only if member is inactive.
      if ($result->Membership->Status != '0') {
        $this->client->MembershipActivateMembership(
          [
            'membership' => [
              'CompanyNo' => $result->Membership->MemberCompanyNo,
              'MemberCompanyNo' => $member->MemberCompanyNo,
              'MemberNo' => $member->MemberNo,
              'Status' => 0,
              'TenancyType' => DEFAULT_TENANCY_TYPE,
              'WaitListType' => $result->Membership->WaitListType,
            ],
          ]
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateMember($member) {
    $member->Type = OS2FORMS_EGBOLIG_MEMBER_DEFAULT_TYPE_UPDATE;
    $member_array = (array) $member;

    // Unsetting the EdgiNo, this sabotages the submission.
    unset($member_array['EgdiNo']);

    // Update member.
    $this->client->MemberUpdate(
      [
        'member' => $member_array,
      ]
    );

    // Update criteria.
    $this->client->MemberUpdateCriterias(
      [
        'member' => $member_array,
      ]
    );

    // Update member education.
    $this->client->MemberUpdateEducation(
      [
        'member' => $member_array,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updatePartner($partner) {
    $partner_array = (array) $partner;
    $this->client->MemberUpdatePartner($partner_array);
  }

  /**
   * {@inheritdoc}
   */
  public function addChild($child) {
    $child_array = (array) $child;

    $this->client->ChildAdd([
      'child' => $child_array,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteChild($child) {
    $child_array = (array) $child;

    $this->client->ChildDelete([
      'child' => $child_array,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWishes($member) {
    $this->client->WishDeleteByMember(
      [
        'memberCompanyNo' => $member->MemberCompanyNo,
        'memberNo' => $member->MemberNo,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDepartments($regionNo) {
    $response = $this->client->DepartmentGetListByRegion([
      'regionNo' => $regionNo,
    ]);

    return $response->DepartmentGetListByRegionResult->Department;
  }

  /**
   * {@inheritdoc}
   */
  public function addWishList($wishes) {
    $this->client->WishAddList(
      [
        'wishes' => $wishes,
      ]
    );
  }

}
