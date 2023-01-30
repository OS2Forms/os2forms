<?php

namespace Drupal\webform_embed\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class WebformEmbedSubscriber.
 */
class WebformEmbedSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.response'] = ['kernel_response'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   */
  public function kernel_response(Event $event) {
    $current_path = \Drupal::service('path.current')->getPath();
    $patterns = "/webform_embed/displayForm/*\n/form/*";
    $match = \Drupal::service('path.matcher')->matchPath($current_path, $patterns);

    if ($match == true) {
      $response = $event->getResponse();
      $response->headers->remove('X-Frame-Options');
    }
  }

}
