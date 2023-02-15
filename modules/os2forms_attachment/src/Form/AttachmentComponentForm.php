<?php

namespace Drupal\os2forms_attachment\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Form handler for the Attachment component add and edit forms.
 */
class AttachmentComponentForm extends EntityForm {

  /**
   * Drupal entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs an AttachmentComponentForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   Webform token manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, WebformTokenManagerInterface $token_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $component = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $component->label(),
      '#description' => $this->t('Name for the component.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $component->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$component->isNew(),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'header' => $this->t('Header'),
        'colophon' => $this->t('Colophon'),
        'footer' => $this->t('Footer'),
      ],
      '#default_value' => $component->getType(),
    ];
    $form['body'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Component content'),
      '#default_value' => $component->getBody(),
      '#format' => 'full_html',
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $form_state->setRedirect('entity.os2forms_attachment_component.list');
  }

  /**
   * Helper function to check whether an component is is already in use.
   *
   * @param int $id
   *   Id of the component.
   *
   * @return bool
   *   True if a Attachment component exists with the given id. FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('os2forms_attachment_component')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
