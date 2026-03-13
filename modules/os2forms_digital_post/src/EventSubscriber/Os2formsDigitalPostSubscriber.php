<?php

namespace Drupal\os2forms_digital_post\EventSubscriber;

use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Event\PrintHtmlAlterEvent;
use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Used to alter the generated PDF to align with digital post requirements.
 */
final class Os2formsDigitalPostSubscriber implements EventSubscriberInterface {

  public function __construct(private readonly SessionInterface $session) {
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
        if ($context = $this->getDigitalPostContext($submission)) {
          $lookupResult = $context['lookupResult'];
          $senderAddress = $context['senderAddress'];

          // Combine address parts.
          $streetAddress = $lookupResult->getStreet();

          if ($lookupResult->getHouseNr()) {
            $streetAddress .= ' ' . $lookupResult->getHouseNr();
          }

          $extendedAddress = '';

          if ($lookupResult->getFloor()) {
            // Add a comma to align with danish address specifications.
            $streetAddress .= ',';
            $extendedAddress = $lookupResult->getFloor();
          }
          if ($lookupResult->getApartmentNr()) {
            $extendedAddress .= ' ' . $lookupResult->getApartmentNr();
          }

          // Generate address HTML.
          $addressHtml = '<div id="envelope-window-digital-post">';
          if (!empty($senderAddress)) {
            $addressHtml .= '<div id="sender-address-digital-post">' . htmlspecialchars($senderAddress) . '</div>';
          }
          $addressHtml .= '<div class="h-card">';
          $addressHtml .= '<div class="p-name">' . htmlspecialchars($lookupResult->getName()) . '</div>';
          if ($lookupResult instanceof CprLookupResult && $lookupResult->getCoName()) {
            $addressHtml .= '<div class="p-name p-co-name">c/o ' . htmlspecialchars($lookupResult->getCoName()) . '</div>';
          }
          $addressHtml .= '<div>';
          $addressHtml .= '<span class="p-street-address">' . htmlspecialchars($streetAddress) . '</span>';
          if (!empty($extendedAddress)) {
            $addressHtml .= ' <span class="p-extended-address">' . htmlspecialchars($extendedAddress) . '</span>';
          }
          $addressHtml .= '</div>';
          $addressHtml .= '<div>';
          $addressHtml .= '<span class="p-postal-code">' . htmlspecialchars($lookupResult->getPostalCode()) . '</span>';
          $addressHtml .= ' <span class="p-locality">' . htmlspecialchars($lookupResult->getCity()) . '</span>';
          $addressHtml .= '</div>';
          $addressHtml .= '</div>';
          $addressHtml .= '</div>';

          // Insert address HTML immediately after body opening tag.
          $html = preg_replace('@<body[^>]*>@', '${0}' . $addressHtml, $html);
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
   * Indicate Digital Post context in the current session.
   */
  public function setDigitalPostContext(WebformSubmissionInterface $submission, CompanyLookupResult|CprLookupResult $lookupResult, string $senderAddress = ''): void {
    $key = $this->createSessionKeyFromSubmission($submission);
    $this->session->set($key, [
      'lookupResult' => $lookupResult,
      'senderAddress' => $senderAddress,
    ]);
  }

  /**
   * Check for Digital Post context in the current session.
   */
  public function getDigitalPostContext(WebformSubmissionInterface $submission): ?array {
    $key = $this->createSessionKeyFromSubmission($submission);

    return $this->session->get($key);
  }

  /**
   * Delete Digital Post context from the current request.
   */
  public function deleteDigitalPostContext(WebformSubmissionInterface $submission): bool {
    $key = $this->createSessionKeyFromSubmission($submission);

    return (bool) $this->session->remove($key);
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
