<?php

namespace Drupal\os2forms_digital_post\EventSubscriber;

use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Event\PrintHtmlAlterEvent;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Used to alter the generated PDF to align with digital post requirements.
 */
final class Os2formsDigitalPostSubscriber implements EventSubscriberInterface {

  public function __construct(private readonly RequestStack $requestStack) {
  }

  /**
   * Post render entity_print event.
   *
   * Injects an envelope-window element containing address information.
   */
  public function onPrintRender(PrintHtmlAlterEvent $event): void {
    $html = &$event->getHtml();

    // Only modify HTML if there is exactly one submission.
    if (count($event->getEntities()) === 1) {
      $submission = $event->getEntities()[0];
      if ($submission instanceof WebformSubmissionInterface) {
        // Check whether generation is for digital post.
        if ($digitalPostContext = $this->getDigitalPostContext($submission)) {
          // Check whether required fields are present.
          if (!isset($digitalPostContext['name'], $digitalPostContext['address'], $digitalPostContext['zipAndCity'])) {
            // Do nothing if they are not.
            return;
          }

          $name = $digitalPostContext['name'];
          $address = $digitalPostContext['address'];
          $zipAndCity = $digitalPostContext['zipAndCity'];

          $addressHtml = <<<HTML
<div class="envelope-window" id="envelope-window-digital-post">
    <div class="envelope-window-recipient-section" id="envelope-window-recipient-section-digital-post">
        $name</br>
        $address</br>
        $zipAndCity
    </div>
</div>
HTML;

          // Insert address HTML immediately after body opening tag.
          $html = preg_replace('@<body[^>]*>@', '${0}' . $addressHtml, $html);
          $this->deleteDigitalPostContext($submission);
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PrintEvents::POST_RENDER => ['onPrintRender'],
    ];
  }

  /**
   * Indicate Digital Post context in the current request.
   */
  public function setDigitalPostContext(WebformSubmissionInterface $submission, array $digitalPostContext): void {
    $key = $this->createSessionKeyFromSubmission($submission);
    $this->requestStack->getCurrentRequest()->getSession()->set($key, $digitalPostContext);
  }

  /**
   * Check for Digital Post context in the current request.
   */
  public function getDigitalPostContext(WebformSubmissionInterface $submission): array {
    $key = $this->createSessionKeyFromSubmission($submission);
    return $this->requestStack->getCurrentRequest()->getSession()->get($key, []);
  }

  /**
   * Delete Digital Post context from the current request.
   */
  public function deleteDigitalPostContext(WebformSubmissionInterface $submission): bool {
    $key = $this->createSessionKeyFromSubmission($submission);
    return (bool) $this->requestStack->getCurrentRequest()->getSession()->remove($key);
  }

  /**
   * Create a session key from a submission that is unique to the submission.
   */
  public function createSessionKeyFromSubmission(WebformSubmissionInterface $submission): string {
    // Due to cloning of submission during attachment logic, we cannot use
    // submission id or uuid. Webform serial, however, is copied along, so a
    // combination of webform id and serial is used for uniqueness.
    // @see \Drupal\os2forms_attachment\Element\AttachmentElement::overrideWebformSettings
    return 'digital_post_context_' . $submission->getWebform()->id() . '_' . $submission->serial();
  }

}
