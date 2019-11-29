<?php

namespace Drupal\os2forms_nemid\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class NemloginRedirectSubscriber implements EventSubscriberInterface {

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
   * {@inheritdoc}
   *
   * @param \Drupal\os2web_nemlogin\Service\AuthProviderService $nemloginAuthProvider
   *   Nemlogin AuthProviderService.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function __construct(AuthProviderService $nemloginAuthProvider, AccountInterface $account) {
    $this->nemloginAuthProvider = $nemloginAuthProvider;
    $this->account = $account;
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

    // This is necessary because this also gets called on
    // webform sub-tabs such as "edit", "revisions", etc.  This
    // prevents those pages from redirected.
    if ($request->attributes->get('_route') !== 'entity.webform.canonical') {
      return;
    }

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $request->attributes->get('webform');
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
      \Drupal::service('page_cache_kill_switch')->trigger();

      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $authProviderPlugin */
      $authProviderPlugin = $this->nemloginAuthProvider->getActivePlugin();

      if (!$authProviderPlugin->isAuthenticated()) {
        // Redirect directly to the external IdP.
        $response = new RedirectResponse($this->nemloginAuthProvider->getLoginUrl()->toString());
        $event->setResponse($response);
        $event->stopPropagation();
      }
      else {
        \Drupal::messenger()
          ->addMessage(t('This webform requires a valid NemID authentication and is not visible without it. You currently have an active NemID authentication session. If you do not want to proceed with this webform press <a href="@logout">log out</a> to return back to the front page.', [
            '@logout' => $this->nemloginAuthProvider->getLogoutUrl(['query' => ['destination' => Url::fromRoute('<front>')->toString()]])
              ->toString(),
          ]));
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
