<?php

namespace Drupal\os2forms_digital_post\EventSubscriber;

use Drupal\beskedfordeler\Event\PostStatusBeskedModtagEvent;
use Drupal\beskedfordeler\EventSubscriber\AbstractBeskedfordelerEventSubscriber;
use Drupal\beskedfordeler\Helper\MessageHelper;
use Drupal\os2forms_digital_post\Helper\BeskedfordelerHelper;
use Drupal\os2forms_digital_post\Helper\WebformHelperSF1601;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Event subscriber for PostStatusBeskedModtagEvent.
 */
final class BeskedfordelerEventSubscriber extends AbstractBeskedfordelerEventSubscriber {
  private const KANAL_KODE = 'Digital Post';
  private const MESSAGE_UUID_KEY = 'MessageUUID';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly BeskedfordelerHelper $beskedfordelerHelper,
    private readonly MessageHelper $messageHelper,
    private readonly WebformHelperSF1601 $webformHelper,
    #[Autowire(service: 'logger.channel.os2forms_digital_post')]
    LoggerInterface $logger,
  ) {
    parent::__construct($logger);
  }

  /**
   * {@inheritdoc}
   */
  protected function processPostStatusBeskedModtag(PostStatusBeskedModtagEvent $event): void {
    $message = $event->getDocument()->saveXML();
    try {
      $data = $this->messageHelper->getBeskeddata($message);

      $channel = $data['KanalKode'] ?? NULL;
      if (self::KANAL_KODE !== $channel) {
        $this->logger->debug('Ignoring message data on channel @channel', [
          '@channel' => $channel ?? '(null)',
        ]);
        return;
      }

      $messageUUID = $data[self::MESSAGE_UUID_KEY] ?? NULL;
      if (NULL === $messageUUID) {
        $this->logger->debug('Missing message UUID (@message_uuid_key) in data on channel @channel: @data', [
          '@message_uuid_key' => self::MESSAGE_UUID_KEY,
          '@channel' => $channel,
          '@data' => json_encode($data),
        ]);
        return;
      }

      if ($this->beskedfordelerHelper->addBeskedfordelerMessage($messageUUID, $message)) {
        $message = $this->beskedfordelerHelper->loadMessage($messageUUID);
        $this->webformHelper->processBeskedfordelerData($message->submissionId, $data);
      }
    }
    catch (\Exception $exception) {
      $this->logger->error('Error processing message: @exception_message', [
        '@exception_message' => $exception->getMessage(),
        'message' => $message,
      ]);
    }
  }

}
