<?php

namespace Drupal\os2forms_attachment\Element;

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
        '#template' => '',
      ];
  }

  /**
   * @inheritDoc
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
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
      // Get scheme.
      $scheme = 'temporary';

      // Get filename.
      $file_name = 'webform-entity-print-attachment--' . $webform_submission->getWebform()->id() . '-' . $webform_submission->id() . '.pdf';

      // Save printable document.
      $print_engine = $print_engine_manager->createSelectedInstance($element['#export_type']);
      $temporary_file_path = $print_builder->savePrintable([$webform_submission], $print_engine, $scheme, $file_name);
      if ($temporary_file_path) {
        $contents = file_get_contents($temporary_file_path);
        \Drupal::service('file_system')->delete($temporary_file_path);
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

}

