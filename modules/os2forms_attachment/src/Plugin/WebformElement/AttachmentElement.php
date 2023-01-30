<?php

namespace Drupal\os2forms_attachment\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentBase;

/**
 * Provides a 'os2forms_attachment' element.
 *
 * @WebformElement(
 *   id = "os2forms_attachment",
 *   label = @Translation("OS2Forms Attachment"),
 *   description = @Translation("Provides a customet OS2forms attachment element."),
 *   category = @Translation("OS2Forms")
 * )
 */
class AttachmentElement extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'view_mode' => 'html',
      'template' => '',
      'export_type' => '',
    ] + parent::defineDefaultProperties();
    // PDF documents should never be trimmed.
    unset($properties['trim']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Require export type file extension.
    $file_extension = 'pdf or html';
    $file_extension_pattern = "(pdf|html)";

    $t_args = ['@extension' => $file_extension];
    $form['attachment']['filename']['#description'] .= '<br/><br/>' . $this->t('File name must include *.@extension file extension.', $t_args);
    $form['attachment']['filename']['#pattern'] = '^.*\.' . $file_extension_pattern . '$';
    $form['attachment']['filename']['#pattern_error'] = $this->t('File name must include *.@extension file extension.', $t_args);
    WebformElementHelper::process($form['attachment']['filename']);

    // View mode.
    $form['attachment']['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => [
        'html' => $this->t('HTML'),
        'table' => $this->t('Table'),
        'twig' => $this->t('Twig template…'),
      ],
    ];
    $form['attachment']['template'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Twig template'),
      '#title_display' => 'invisible',
      '#mode' => 'twig',
      '#states' => [
        'visible' => [
          ':input[name="properties[view_mode]"]' => ['value' => 'twig'],
        ],
      ],
    ];
    $form['attachment']['help'] = WebformTwigExtension::buildTwigHelp() + [
      '#states' => [
        'visible' => [
          ':input[name="properties[view_mode]"]' => ['value' => 'twig'],
        ],
      ],
    ];
    $form['attachment']['export_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Export type'),
      '#options' => [
        'pdf' => $this->t('PDF'),
        'html' => $this->t('HTML'),
      ],
    ];
    // Set #access so that help is always visible.
    WebformElementHelper::setPropertyRecursive($form['attachment']['help'], '#access', TRUE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportAttachmentsBatchLimit() {
    return 10;
  }

}
