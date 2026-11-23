<?php

namespace Drupal\os2forms_attachment\Element;

use Drupal\os2forms_attachment\Os2formsAttachmentPrintBuilder;
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
      '#digital_signature_position' => Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT,
      '#template' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    // Override some webform settings before rendering PDF.
    $webform = $webform_submission->getWebform();
    $originalWebformSettings = [
      'submission_excluded_elements' => $webform->getSetting('submission_excluded_elements'),
      'submission_exclude_empty' => $webform->getSetting('submission_exclude_empty'),
      'submission_exclude_empty_checkbox' => $webform->getSetting('submission_exclude_empty_checkbox'),
    ];
    // Override with settings from attachment element.
    $newWebformSettings = [
      'submission_excluded_elements' => $element['#excluded_elements'] ?? FALSE,
      'submission_exclude_empty' => $element['#exclude_empty'] ?? FALSE,
      'submission_exclude_empty_checkbox' => $element['#exclude_empty_checkbox'] ?? FALSE,
    ];
    try {
      foreach ($newWebformSettings as $key => $value) {
        $webform->setSetting($key, $value);
      }

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

        // Digital signature settings live on the digital signature handler.
        // Resolving them here ensures all consumers of the element (email
        // handlers, attachment downloads) serve an already signed document
        // and render the validation text consistently.
        // Webform skips handlers whose per-submission conditions do not match,
        // so the same check is applied here to avoid rendering signature
        // validation text on a document that was never signed.
        $elementKey = $element['#webform_key'] ?? NULL;
        if ($elementKey !== NULL) {
          foreach ($webform_submission->getWebform()->getHandlers('os2forms_digital_signature') as $handler) {
            $settings = $handler->getConfiguration()['settings'] ?? [];
            if ($handler->isEnabled()
              && $handler->checkConditions($webform_submission)
              && ($settings['attachment_element'] ?? '') === $elementKey) {
              $element['#digital_signature'] = TRUE;
              $element['#digital_signature_position'] = $settings['signature_position']
                ?? Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT;
              break;
            }
          }
        }

        // If attachment with digital signatur, check if we already have one.
        if (isset($element['#digital_signature']) && $element['#digital_signature']) {
          // Get scheme.
          $scheme = 'private';

          // Get filename.
          $file_name = 'webform/' . $webform_submission->getWebform()->id() . '/digital_signature/' . $webform_submission->uuid() . '.pdf';
          $file_path = "$scheme://$file_name";
        }

        if (!$file_path || !file_exists($file_path)) {
          // Get scheme.
          $scheme = 'temporary';
          // Get filename.
          $file_name = 'webform-entity-print-attachment--' . $webform_submission->getWebform()->id() . '-' . $webform_submission->id() . '.pdf';

          // Save printable document.
          $print_engine = $print_engine_manager->createSelectedInstance($element['#export_type']);

          // Adding digital signature.
          if (isset($element['#digital_signature']) && $element['#digital_signature']) {
            $signaturePosition = $element['#digital_signature_position'] ?? Os2formsAttachmentPrintBuilder::SIGNATURE_POSITION_AFTER_CONTENT;
            $file_path = $print_builder->savePrintableDigitalSignature([$webform_submission], $print_engine, $scheme, $file_name, TRUE, $signaturePosition);
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
    } finally {
      // Restore webform settings.
      foreach ($originalWebformSettings as $key => $value) {
        $webform->setSetting($key, $value);
      }
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

}
