<?php

namespace Drupal\os2forms\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms\Helper\HandlerConfigurationValidator;
use Drupal\webform\WebformInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Reports misconfigured webform handlers on webform admin pages.
 *
 * @see \Drupal\os2forms\Helper\HandlerConfigurationValidator
 */
class HandlerConfigurationValidationSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Routes on which to report misconfigured handlers.
   */
  private const ROUTE_NAMES = [
    // The webform build page (where elements are renamed or deleted).
    'entity.webform.edit_form',
    // The webform handlers page.
    'entity.webform.handlers',
  ];

  /**
   * Constructs the HandlerConfigurationValidationSubscriber.
   *
   * @param \Drupal\os2forms\Helper\HandlerConfigurationValidator $validator
   *   The handler configuration validator.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    private readonly HandlerConfigurationValidator $validator,
    private readonly RouteMatchInterface $routeMatch,
    private readonly MessengerInterface $messenger,
  ) {
  }

  /**
   * Reports misconfigured webform handlers to the current user.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event): void {
    if (!in_array($this->routeMatch->getRouteName(), self::ROUTE_NAMES, TRUE)) {
      return;
    }

    // Only report on regular page views.
    $request = $event->getRequest();
    if (!$request->isMethod('GET') || $request->isXmlHttpRequest()) {
      return;
    }

    $webform = $this->routeMatch->getParameter('webform');
    if (!$webform instanceof WebformInterface) {
      return;
    }

    foreach ($this->validator->validate($webform) as $handlerId => $result) {
      foreach ($result['problems'] as $problem) {
        $this->messenger->addWarning($this->t('The handler @handler (@id) is misconfigured: @problem', [
          '@handler' => $result['label'],
          '@id' => $handlerId,
          '@problem' => $problem,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, mixed>
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest'],
    ];
  }

}
