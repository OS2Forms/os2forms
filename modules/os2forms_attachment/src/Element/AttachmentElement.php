<?php

namespace Drupal\os2forms_attachment\Element;

use Drupal\Core\File\FileSystemInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;

/**
 * Provides OS2forms attachment element.
 *
 * @FormElement("os2forms_attachment")
 */
class AttachmentElement extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#view_mode' => 'html',
      '#export_type' => 'pdf',
      '#digital_signature' => FALSE,
      '#template' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    $submissionUuid = $webform_submission->uuid();

    // Override webform settings.
    static::overrideWebformSettings($element, $webform_submission);

    /** @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface $print_engine_manager */
    $print_engine_manager = \Drupal::service('plugin.manager.entity_print.print_engine');

    /** @var \Drupal\os2forms_attachment\Os2formsAttachmentPrintBuilder $print_builder */
    $print_builder = \Drupal::service('os2forms_attachment.print_builder');

    // Make sure Webform Entity Print template is used.
    // @see webform_entity_print_entity_view_alter()
    \Drupal::request()->request->set('_webform_entity_print', TRUE);

    // Set view mode or render custom twig.
    // @see \Drupal\webform\WebformSubmissionViewBuilder::view
    // @see webform_entity_print_attachment_webform_submission_view_alter()
    $view_mode = $element['#view_mode'] ?? 'html';
    if ($view_mode === 'twig') {
      $webform_submission->_webform_view_mode_twig = $element['#template'];
    }
    \Drupal::request()->request->set('_webform_submissions_view_mode', $view_mode);

    if ($element['#export_type'] === 'pdf') {
      $file_path = NULL;

      // If attachment with digital signatur, check if we already have one.
      if (isset($element['#digital_signature']) && $element['#digital_signature']) {
        // Get scheme.
        $scheme = 'private';

        // Get filename.
        $file_name = 'webform/' . $webform_submission->getWebform()->id() . '/digital_signature/' . $submissionUuid . '.pdf';
        $file_path = "$scheme://$file_name";
      }

      if (!$file_path || !file_exists($file_path)) {
        // Get scheme.
        $scheme = 'temporary';
        // Get filename.
        $file_name = 'webform-entity-print-attachment--' . $webform_submission->getWebform()->id() . '-' . $webform_submission->id() . '.pdf';

        // Save printable document.
        $print_engine = $print_engine_manager->createSelectedInstance($element['#export_type']);

        // Adding digital signature
        if (isset($element['#digital_signature']) && $element['#digital_signature']) {
          $file_path = $print_builder->savePrintableDigitalSignature([$webform_submission], $print_engine, $scheme, $file_name);
        }
        else {
          $file_path = $print_builder->savePrintable([$webform_submission], $print_engine, $scheme, $file_name);
        }
      }

      if ($file_path) {
        $contents = file_get_contents($file_path);

        // Deleting temporary file.
        if ($scheme == 'temporary') {
          \Drupal::service('file_system')->delete($file_path);
        }
      }
      else {
        // Log error.
        $context = ['@filename' => $file_name];
        \Drupal::logger('webform_entity_print')->error("Unable to generate '@filename'.", $context);
        $contents = '';
      }
    }
    else {
      // Save HTML document.
      $contents = $print_builder->printHtml($webform_submission);
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileName(array $element, WebformSubmissionInterface $webform_submission) {
    if (empty($element['#filename'])) {
      return $element['#webform_key'] . '.' . $element['#export_type'];
    }
    else {
      return parent::getFileName($element, $webform_submission);
    }
  }

  /**
   * Overrides connected webform settings.
   *
   * Does that by creating a duplicate webform element with connected to a
   * webform with the settings updated.
   *
   * @param array $element
   *   The webform attachment element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public static function overrideWebformSettings(array $element, WebformSubmissionInterface &$webform_submission) {
    $webform = $webform_submission->getWebform();

    // Rewriting webform settings.
    $webform->setSetting('submission_excluded_elements', $element['#excluded_elements'] ?? FALSE);
    $webform->setSetting('submission_exclude_empty', $element['#exclude_empty'] ?? FALSE);
    $webform->setSetting('submission_exclude_empty_checkbox', $element['#exclude_empty_checkbox'] ?? FALSE);

    // Creating temporary submission to be able to swap original webform
    // settings.
    $webform_submission_temp = WebformSubmission::create([
      'webform' => $webform,
      'entity_type' => $webform_submission->getEntityTypeId(),
      'entity_id' => $webform_submission->id(),
      'data' => $webform_submission->getData(),
    ]);
    // Clone ids.
    $webform_submission_temp->set('serial', $webform_submission->get('serial')->value);
    $webform_submission_temp->set('token', $webform_submission->get('token')->value);
    // Clone states.
    $webform_submission_temp->set('in_draft', $webform_submission->get('in_draft')->value);
    $webform_submission_temp->set('current_page', $webform_submission->get('current_page')->value);
    // Clone timestamps.
    $webform_submission_temp->set('created', $webform_submission->get('created')->value);
    $webform_submission_temp->set('changed', $webform_submission->get('changed')->value);
    $webform_submission_temp->set('completed', $webform_submission->get('completed')->value);
    // Clone admin notes, sticky, and locked.
    $webform_submission_temp->set('notes', $webform_submission->get('notes')->value);
    $webform_submission_temp->set('sticky', $webform_submission->get('sticky')->value);
    $webform_submission_temp->set('sticky', $webform_submission->get('locked')->value);

    // Finalize cloning: swap the webform submission.
    $webform_submission = $webform_submission_temp;
  }

}
