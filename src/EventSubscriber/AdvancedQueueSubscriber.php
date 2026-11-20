<?php

declare(strict_types=1);

namespace Drupal\os2forms\EventSubscriber;

use Drupal\advancedqueue\Event\AdvancedQueueEvents;
use Drupal\advancedqueue\Event\JobEvent;
use Drupal\webform\Entity\WebformSubmission;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for Advanced Queue events.
 */
final class AdvancedQueueSubscriber implements EventSubscriberInterface {

  public function __construct(
    #[Autowire(service: 'logger.channel.os2forms')]
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Resets static Webform submissions state before processing a job.
   *
   * If multiple submissions are processed in one (Drush) request and one of
   * them activates a webform getting the content of an OS2Forms attachment
   * element, all submissions afterwards will use the same webform as the first
   * submission (see <https://github.com/OS2Forms/os2forms/issues/393> for some
   * details).
   *
   * @see WebformSubmission::getWebform()
   * @see WebformSubmission::$webform
   */
  public function onPreProcess(JobEvent $event): void {
    try {
      $property = new \ReflectionProperty(WebformSubmission::class, 'webform');
      $property->setValue(NULL);
    }
    catch (\ReflectionException $exception) {
      $this->logger->error('Cannot reset webform submission webform state: @message', [
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // We want our subscriber to run very early (cf.
      // https://www.drupal.org/docs/develop/creating-modules/subscribe-to-and-dispatch-events#s-event-subscriber-priorities)
      AdvancedQueueEvents::PRE_PROCESS => ['onPreProcess', 999_999],
    ];
  }

}
