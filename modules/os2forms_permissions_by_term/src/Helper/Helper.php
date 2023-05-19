<?php

namespace Drupal\os2forms_permissions_by_term\Helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;

/**
 * Helper class for os2forms permissions by term.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * Permissions by term access storage.
   *
   * @var \Drupal\permissions_by_term\Service\AccessStorage
   */
  private AccessStorage $accessStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $account;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * Helper constructor.
   *
   * @param \Drupal\permissions_by_term\Service\AccessStorage $accessStorage
   *   The permissions by term access storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The Account proxy interface.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   */
  public function __construct(AccessStorage $accessStorage, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account, ConfigFactory $configFactory) {
    $this->accessStorage = $accessStorage;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->configFactory = $configFactory;
  }

  /**
   * Implementation of hook_form_FORM_ID_alter().
   *
   * Add permission by term selection to webform "add" and "settings".
   *
   * @param array $form
   *   The form being altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   * @param string $hook
   *   The type of webform hook calling this method.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function webformAlter(array &$form, FormStateInterface $form_state, $hook) {
    /** @var \Drupal\Core\Entity\EntityForm $formObject */
    $formObject = $form_state->getFormObject();
    $node = $formObject->getEntity();
    if ('webform' !== $node->bundle()) {
      return;
    }
    $term_data = [];
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
    if (1 === (int) $this->account->id()) {
      $userTerms = [];
      $permissionsByTermBundles = $this->configFactory->get('permissions_by_term.settings')->get('target_bundles');
      foreach ($permissionsByTermBundles as $bundle) {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($bundle);
        foreach ($terms as $term) {
          $userTerms[] = $term->tid;
        }
      }
    }
    else {
      $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($userTerms);
    foreach ($terms as $term) {
      $term_data[$term->id()] = $term->label();
    }

    // Remove any options that allow anonymous access to the webform.
    $anonymousTerms = $this->accessStorage->getPermittedTids(0, ['anonymous']);
    foreach ($anonymousTerms as $termId) {
      unset($term_data[$termId]);
    }

    // Make sure title is first when creating a new webform.
    if ('add' === $hook) {
      $form['title']['#weight'] = -100;
    }

    // Get default settings for webform.
    if ('settings' === $hook) {
      /** @var \Drupal\Core\Entity\EntityForm $webform_settings_form */
      $webform_settings_form = $form_state->getFormObject();
      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $webform_settings_form->getEntity();
      $defaultSettings = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
    }

    $form['os2forms_permissions_by_term'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Webform access'),
      '#tree' => TRUE,
      '#weight' => -99,
    ];

    $form['os2forms_permissions_by_term']['os2forms_access'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => $this->t('Access'),
      '#default_value' => $defaultSettings ?? [],
      '#options' => $term_data,
      '#description' => $this->t('Limit access to this webform.'),
    ];

    // Set access value automatically if user only has one term option.
    if ('add' === $hook && 1 === count($term_data)) {
      $form['os2forms_permissions_by_term']['os2forms_access']['#disabled'] = TRUE;
      $form['os2forms_permissions_by_term']['os2forms_access']['#value'] = [array_key_first($term_data) => array_key_first($term_data)];
    }

    $form['actions']['submit']['#submit'][] = [$this, 'webformSubmit'];
  }

  /**
   * Implementation of hook_ENTITY_TYPE_access().
   *
   * Check access on webform related operations.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform we check access for.
   * @param string $operation
   *   The operation being performed on the webform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The resulting access permission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function webformAccess(WebformInterface $webform, $operation, AccountInterface $account) {
    if (1 == $account->id()) {
      return AccessResult::neutral();
    }
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    $webformPermissionsByTerm = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
    switch ($operation) {
      case 'view':
        // We don't use permission by term to determine access to the actual
        // webform.
        // This could probably be removed, but is left in to show we are aware
        // of this operation.
        return AccessResult::neutral();

      case 'update':
      case 'delete':
      case 'duplicate':
      case 'test':
      case 'submission_page':
      case 'submission_view_any':
      case 'submission_view_own':
      case 'submission_purge_any':
        // Allow access if no term is set for the form or a webform term match
        // the users term.
        return empty($webformPermissionsByTerm) || !empty(array_intersect($webformPermissionsByTerm, $userTerms))
          ? AccessResult::neutral()
          : AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * Implementation of hook_ENTITY_TYPE_access().
   *
   * Check access on node related operations.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param string $operation
   *   The operation being performed on the node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return mixed
   *   The resulting access permission.
   */
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    if ('webform' === $node->bundle()) {
      switch ($operation) {
        case 'view':
          // Deny access to node view if no permission by term is set.
          $nodePermissionsByTerm = $node->field_os2forms_permissions->getValue();
          return empty($nodePermissionsByTerm)
            ? AccessResult::forbidden()
            : AccessResult::neutral();
      }
    }
  }

  /**
   * Custom submit handler for webform add/edit form.
   *
   * Set permission by term as a thirdPartySetting of the webform.
   *
   * @param array $form
   *   The webform add/edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function webformSubmit(array $form, FormStateInterface $form_state) {
    // Get the settings from the webform config entity.
    /** @var \Drupal\Core\Entity\EntityForm $webform_settings_form */
    $webform_settings_form = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webform_settings_form->getEntity();
    $webform->setThirdPartySetting(
      'os2forms_permissions_by_term',
      'settings',
      $form_state->getValue([
        'os2forms_permissions_by_term',
        'os2forms_access',
      ]));
    $webform->save();
  }

  /**
   * Implementation of hook_form_FORM_ID_alter().
   *
   * Add permission by term selection to node "add" and "edit".
   *
   * @param array $form
   *   The form being altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function nodeFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityForm $formObject */
    $formObject = $form_state->getFormObject();
    $nodeBundle = $formObject->getEntity()->bundle();
    if (1 === (int) $this->account->id() || 'webform' !== $nodeBundle) {
      return;
    }

    // Run custom submit handler before default node submission.
    array_unshift(
      $form['actions']['submit']['#submit'],
      [$this, 'nodeWebformPermisisonsByTermSubmit']
    );
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    $anonymousTerms = $this->accessStorage->getPermittedTids(0, ['anonymous']);
    $webformReference = $form['webform']['widget'][0]['target_id']['#default_value'];
    // If a webform is referenced from the node add message.
    if ($webformReference) {
      $url = URL::fromRoute('entity.webform.settings_access', ['webform' => $webformReference])->toString();
      $form['field_os2forms_permissions']['widget'][0]['#prefix'] =
        '<div class="alert alert-warning">' . $this->t('Anonymous access to view this content is set on <a href="@url">the related webform access page</a> . (Create submissions permission)', ['@url' => $url]) . '</div>';
    }
    // Disable anonymous terms. They should always be fetched from webform.
    foreach ($anonymousTerms as $termId) {
      $form['field_os2forms_permissions']['widget'][$termId]['#disabled'] = TRUE;
    }

    // Set access value automatically if user only has one term option.
    if (1 === count($userTerms)) {
      $form['field_os2forms_permissions']['widget']['#disabled'] = TRUE;
      $form['field_os2forms_permissions']['widget']['#default_value'][] = $userTerms[0];
    }
  }

  /**
   * Implements hook_field_widget_multivalue_WIDGET_TYPE_form_alter().
   *
   * Alter the field webform_entity_reference widget.
   * Hide options if user is not allowed to access the webform.
   *
   * @param array $elements
   *   The form element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function fieldWidgetWebformEntityReferenceFormAlter(array &$elements) {
    $options = $elements[0]['target_id']['#options'];
    $result = [];
    $this->filterWebformSelectOptions($options, $result);
    $elements[0]['target_id']['#options'] = $result;
  }

  /**
   * Implements hook_options_list_alter().
   *
   * Change options list field for node.field_os2forms_permissions.
   * Add anonymous option to allow the form to be displayed for anonymous users.
   *
   * @param array $options
   *   The options of the list.
   * @param array $context
   *   The context of the options list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function optionsListAlter(array &$options, array $context) {
    // Alter the field_os2forms_permissions options list.
    if ('node.field_os2forms_permissions' !== $context['fieldDefinition']->getFieldStorageDefinition()->id()) {
      return;
    }
    // Limit options to those available on user profile.
    $options = [];
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    foreach ($userTerms as $userTerm) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($userTerm);
      $options[$userTerm] = $term->label();
    }
    $anonymousTerms = $this->accessStorage->getPermittedTids(0, ['anonymous']);
    foreach ($anonymousTerms as $termId) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($termId);
      $label = $this->t('@term_label (Note: View permission only. This setting depends on the related webform.)', ['@term_label' => $term->label()]);
      $options = [$termId => $label] + $options;
    }
  }

  /**
   * Custom submit handler for setting permissions by term on node.
   *
   * @param array $form
   *   The form that is being submitted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form being submitted.
   */
  public function nodeWebformPermisisonsByTermSubmit(array $form, FormStateInterface $form_state) {
    $webformReference = $form_state->getValue('webform');
    $webformTarget = $webformReference['0']['target_id'] ?? NULL;
    if (!$webformTarget) {
      return;
    }
    $existingValues = $form_state->getValue('field_os2forms_permissions');
    $anonymousTerms = $this->accessStorage->getPermittedTids(0, ['anonymous']);
    $anonymousUser = User::getAnonymousUser();
    $referencedWebform = $this->entityTypeManager->getStorage('webform')->load($webformTarget);
    foreach ($anonymousTerms as $termId) {
      if ($referencedWebform->access('submission_create', $anonymousUser)) {
        $existingValues[] = ['target_id' => $termId];
      }
    }
    $form_state->setValue('field_os2forms_permissions', $existingValues);
  }

  /**
   * Add to the private variable webformSelectOptions.
   *
   * @param array $options
   *   The options to to pick from.
   * @param array $result
   *   The result.
   * @param string|null $parent
   *   A parent key if the option is a child.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function filterWebformSelectOptions(array $options, array &$result = [], string $parent = NULL) {
    foreach ($options as $key => $option) {
      if ($option instanceof FieldFilteredMarkup) {
        $webform = $this->entityTypeManager->getStorage('webform')->load($key);
        /** @var \Drupal\webform\WebformInterface $webform */
        $accessResult = $this->webformAccess($webform, 'update', $this->account);
        if (!$accessResult instanceof AccessResultForbidden) {
          if ($parent) {
            // Webform module only allows for one level of grouping, so we can
            // safely assume only one level nesting.
            $result[$parent][$key] = $option;
          }
          else {
            $result[$key] = $option;
          }
        }
      }
      else {
        if (is_array($option)) {
          $this->filterWebformSelectOptions($option, $result, $key);
        }
      }
    }
  }

}
