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
      'digital_signature' => '',
      'exclude_empty' => '',
      'exclude_empty_checkbox' => '',
      'excluded_elements' => '',
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
    $form['attachment']['digital_signature'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Digital signature'),
    ];

    // Set #access so that help is always visible.
    WebformElementHelper::setPropertyRecursive($form['attachment']['help'], '#access', TRUE);

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included elements'),
      '#description' => $this->t('The selected elements will be included in the [webform_submission:values] token. Individual values may still be printed if explicitly specified as a [webform_submission:values:?] in the email body template.'),
      '#open' => $this->configuration['excluded_elements'] ? TRUE : FALSE,
    ];
    $form['elements']['excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#exclude_markup' => FALSE,
      '#webform_id' => $this->webform->id(),
      '#default_value' => $this->configuration['excluded_elements'],
    ];
    $form['elements']['exclude_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude empty elements'),
      '#description' => $this->t('If checked, empty elements will be excluded from the email values.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['exclude_empty'],
    ];
    $form['elements']['exclude_empty_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude unselected checkboxes'),
      '#description' => $this->t('If checked, empty checkboxes will be excluded from the email values.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['exclude_empty_checkbox'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportAttachmentsBatchLimit() {
    return 10;
  }

}
