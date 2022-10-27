<?php

namespace Drupal\os2forms_nemid\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Drupal\os2forms_nemid\Form\SettingsForm;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class NemloginRedirectSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The SimpleSAML Authentication helper service.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  protected $nemloginAuthProvider;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Messenger object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The page cache disabling policy.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\os2web_nemlogin\Service\AuthProviderService $nemloginAuthProvider
   *   Nemlogin AuthProviderService.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger object.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The page cache disabling policy.
   */
  public function __construct(
    AuthProviderService $nemloginAuthProvider,
    AccountInterface $account,
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    KillSwitch $page_cache_kill_switch) {
    $this->nemloginAuthProvider = $nemloginAuthProvider;
    $this->account = $account;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
  }

  /**
   * Redirects to nemlogin authentication url.
   *
   * Only if current webform has nemlogin_auto_redirect on.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The subscribed event.
   */
  public function redirectToNemlogin(GetResponseEvent $event) {
    $request = $event->getRequest();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = NULL;

    // Try to find a webform from the request.
    $route = $request->attributes->get('_route');
    switch ($route) {
      // Creating a new submission.
      case 'entity.webform.canonical':
        $webform = $request->attributes->get('webform');
        break;

      // Editing a submission.
      case'entity.webform_submission.edit_form':
        $webformSubmission = $request->attributes->get('webform_submission');
        $webform = $webformSubmission->getWebform();
        break;

      // Webform attached to a node.
      case 'entity.node.canonical':
        $node = $request->attributes->get('node');
        $nodeType = $node->getType();

        // Search if this node type is related with field of type 'webform'.
        $webformFieldMap = $this->entityFieldManager->getFieldMapByFieldType('webform');
        if (isset($webformFieldMap['node'])) {
          foreach ($webformFieldMap['node'] as $field_name => $field_meta) {
            // We found field of type 'webform' in this node, let's try fetching
            // the webform.
            if (in_array($nodeType, $field_meta['bundles'])) {
              if ($webformId = $node->get($field_name)->target_id) {
                $webform = Webform::load($webformId);
                break;
              }
            }
          }
        }
        break;
    }

    // If we don't have any webform.
    if (!$webform) {
      return;
    }

    $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // Getting nemlogin_auto_redirect setting.
    $nemlogin_auto_redirect = NULL;
    if (isset($webformNemidSettings['nemlogin_auto_redirect'])) {
      $nemlogin_auto_redirect = $webformNemidSettings['nemlogin_auto_redirect'];
    }
    // Checking if $nemlogin_auto_redirect is on.
    if ($nemlogin_auto_redirect) {
      // Killing cache so that positive or negative redirect decision is not
      // cached.
      $this->pageCacheKillSwitch->trigger();

      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
      $authProviderPlugin = $this->nemloginAuthProvider->getActivePlugin();

      if (!$authProviderPlugin->isAuthenticated()) {
        // Redirect directly to the external IdP.
        $response = new RedirectResponse($this->nemloginAuthProvider->getLoginUrl()->toString());
        $event->setResponse($response);
        $event->stopPropagation();
      }
      else {
        $settingFormConfig = $this->configFactory->get(SettingsForm::$configName);
        if (!$settingFormConfig->get('os2forms_nemid_hide_active_nemid_session_message')) {
          $this->messenger
            ->addMessage($this->t('This webform requires a valid NemID authentication and is not visible without it. You currently have an active NemID authentication session. If you do not want to proceed with this webform press <a href="@logout">log out</a> to return back to the front page.', [
              '@logout' => $this->nemloginAuthProvider->getLogoutUrl(['query' => ['destination' => Url::fromRoute('<front>')->toString()]])
                ->toString(),
            ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirectToNemlogin'];
    return $events;
  }

}
