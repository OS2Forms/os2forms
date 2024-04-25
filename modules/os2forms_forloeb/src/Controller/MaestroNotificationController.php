<?php

namespace Drupal\os2forms_forloeb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\os2forms_digital_post\Model\Document;
use Drupal\os2forms_forloeb\MaestroHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maestro notification controller.
 */
class MaestroNotificationController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly WebformSubmissionStorageInterface $webformSubmissionStorage,
    private readonly MaestroHelper $maestroHelper,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform_submission'),
      $container->get(MaestroHelper::class)
    );
  }

  /**
   * Preview notification action.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param string $handler
   *   The handler ID.
   * @param string $notification_type
   *   The notification type.
   * @param string $content_type
   *   The content type.
   *
   * @return array
   *   A render array.
   */
  public function preview(Request $request, WebformInterface $webform, string $handler, string $notification_type, string $content_type): array {
    $handler = $webform->getHandler($handler);
    $submissionIds = array_keys($this->webformSubmissionStorage->getQuery()
      ->condition('webform_id', $webform->id())
      ->sort('created', 'DESC')
      ->execute());
    $currentSubmission = (int) $request->query->get('submission');
    $index = array_search($currentSubmission, $submissionIds);
    if (FALSE === $index) {
      $currentSubmission = reset($submissionIds) ?: NULL;
      $index = array_search($currentSubmission, $submissionIds);
    }

    $previewUrls = array_map(
      static fn ($submission) => Url::fromRoute('os2forms_forloeb.meastro_notification.preview', [
        'webform' => $webform->id(),
        'handler' => $handler->getHandlerId(),
        'content_type' => $content_type,
        'submission' => $submission,
      ]),
      array_filter([
        'prev' => $submissionIds[$index + 1] ?? NULL,
        'self' => $currentSubmission,
        'next' => $submissionIds[$index - 1] ?? NULL,
      ])
    );

    $renderUrl = NULL !== $currentSubmission
      ? Url::fromRoute('os2forms_forloeb.meastro_notification.preview_render', [
        'webform' => $webform->id(),
        'handler' => $handler->getHandlerId(),
        'notification_type' => $notification_type,
        'content_type' => $content_type,
        'submission' => $currentSubmission,
      ])
    : NULL;

    $submission = $this->webformSubmissionStorage->load($currentSubmission);
    $templateTask = [];
    $maestroQueueID = 0;
    [
      'recipient' => $recipient,
      'subject' => $subject,
    ] = $this->maestroHelper->renderNotification($submission, $handler->getHandlerId(), $notification_type, $templateTask, $maestroQueueID, $content_type);

    return [
      '#theme' => 'os2forms_forloeb_notification_preview',
      '#webform' => $webform,
      '#handler' => $handler,
      '#notification_type' => $notification_type,
      '#subject' => $subject,
      '#recipient' => $recipient,
      '#content_type' => $content_type,
      '#submission' => $currentSubmission,
      '#return_url' => $webform->toUrl('handlers'),
      '#render_url' => $renderUrl,
      '#preview_urls' => $previewUrls,
    ];
  }

  /**
   * Render notification preview.
   *
   * @param string $handler
   *   The handler ID.
   * @param string $notification_type
   *   The notification type.
   * @param string $content_type
   *   The content type.
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function previewRender(string $handler, string $notification_type, string $content_type, WebformSubmissionInterface $submission) {
    $templateTask = [];
    $maestroQueueID = 0;
    [
      'content' => $content,
      'contentType' => $contentType,
    ] = $this->maestroHelper->renderNotification($submission, $handler, $notification_type, $templateTask, $maestroQueueID, $content_type);

    $response = new Response($content);
    if ('pdf' === $contentType) {
      $response->headers->set('content-type', Document::MIME_TYPE_PDF);
    }

    return $response;
  }

  /**
   * Message action.
   *
   * Used only to show a message when a Maestro task link from a notification
   * preview is clicked.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function message(Request $request): Response {
    $content[] = '<h1>' . $request->get('message') . '</h1>';
    if ($referer = $request->headers->get('referer')) {
      $content[] = $this->t('<a href=":url">Back to preview</a>', [':url' => $referer]);
    }

    return new Response(implode(PHP_EOL, $content));
  }

}
