<?php

namespace Drupal\field_color\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_color\Component\FieldColorValidation;

/**
 * Provides a Color element.
 *
 * @FormElement("input_color")
 */
class ColorInput extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processTestElement'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderColor'],
        [$class, 'preRenderGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateColor'],
      ],
      '#theme' => 'input_color',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processTestElement(&$element, FormStateInterface $form_state, &$form) {
    return $element;
  }

  /**
   * Prepares a #type 'color' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderColor(array $element) {
    $element['#attributes']['type'] = 'text';
    $element['#attributes']['class'] = [];
    $element['#attributes']['class'][] = 'input-field-color';
    Element::setAttributes($element, [
      'id', 'name', 'value', 'class', 'size', 'field-color',
    ]);

    $element['#palette'] = preg_replace("/[\r\n]+/", "", $element['#palette']);
    $element['#selectionPalette'] = preg_replace("/[\r\n]+/", "", $element['#selectionPalette']);
    $element['#default_value'] = $element['#color'];

    $element['#attached'] = [
      'library' => [
        'field_color/field_color.admin',
      ],
      'drupalSettings' => [
        'size' => $element['#size'] ?? 22,
        'type_' => $element['#type_'] ?? 'component',
//        'color' => $element['#color'],
        'showInput' => (Boolean)($element['#showInput'] ?? true),
        'showInitial' => (Boolean)($element['#showInitial'] ?? false),
        'allowEmpty' => (Boolean)($element['#allowEmpty'] ?? false),
        'showAlpha' => (Boolean)($element['#showAlpha'] ?? true),
        'disabled' => (Boolean)($element['#disabled'] ?? false),
        'localStorageKey' => $element['#localStorageKey'] ?? false,
        'showPalette' => (Boolean)($element['#showPalette'] ?? true),
        'showButtons' => (Boolean)($element['#showButtons'] ?? true),
        'showPaletteOnly' => (Boolean)($element['#showPaletteOnly'] ?? false),
        'togglePaletteOnly' => (Boolean)($element['#togglePaletteOnly'] ?? true),
        'showSelectionPalette' => (Boolean)($element['#showSelectionPalette'] ?? true),
        'clickoutFiresChange' => (Boolean)($element['#clickoutFiresChange'] ?? true),
        'hideAfterPaletteSelect' => (Boolean)($element['#hideAfterPaletteSelect'] ?? true),
        'containerClassName' => $element['#containerClassName'] ?? '',
        'replacerClassName' => $element['#replacerClassName'] ?? '',
        'preferredFormat' => $element['#preferredFormat'] ?? 'hex',
        'cancelText' => $element['#cancelText'] ?? 'cancel',
        'chooseText' => $element['#chooseText'] ?? 'choose',
        'togglePaletteMoreText' => $element['#togglePaletteMoreText'] ?? 'more',
        'togglePaletteLessText' => $element['#togglePaletteLessText'] ?? 'less',
        'clearText' => $element['#clearText'] ?? 'Clear Color Selection',
        'noColorSelectedText' => $element['#noColorSelectedText'] ?? 'No Color Selected',
        'palette' => json_decode($element['#palette'], TRUE) ?? [
          ["#000", "#444", "#5b5b5b", "#999", "#bcbcbc", "#eee", "#f3f6f4", "#fff"],
          ["#f44336", "#744700", "#ce7e00", "#8fce00", "#2986cc", "#16537e", "#6a329f", "#c90076"],
          ["#f4cccc", "#fce5cd", "#fff2cc", "#d9ead3", "#d0e0e3", "#cfe2f3", "#d9d2e9", "#ead1dc"],
          ["#ea9999", "#f9cb9c", "#ffe599", "#b6d7a8", "#a2c4c9", "#9fc5e8", "#b4a7d6", "#d5a6bd"],
          ["#e06666", "#f6b26b", "#ffd966", "#93c47d", "#76a5af", "#6fa8dc", "#8e7cc3", "#c27ba0"],
          ["#c00", "#e69138", "#f1c232", "#6aa84f", "#45818e", "#3d85c6", "#674ea7", "#a64d79"],
          ["#900", "#b45f06", "#bf9000", "#38761d", "#134f5c", "#0b5394", "#351c75", "#741b47"],
          ["#600", "#783f04", "#7f6000", "#274e13", "#0c343d", "#073763", "#20124d", "#4c1130"]
        ],
        'selectionPalette' => json_decode($element['#selectionPalette'])  ?? [],
        'maxSelectionSize' => $element['#maxSelectionSize'] ?? 8,
        'locale' => $element['#locale'] ?? 'en',
      ],
    ];

    return $element;
  }

  /**
   * Form element validation handler for #type 'color'.
   */
  public static function validateColor(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    if (!empty($value) && !FieldColorValidation::all($value)) {
      error_log($value);
      $form_state->setError($element, t('Field %name must be a valid color.', ['%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // This should be a string, but allow other scalars since they might be
      // valid input in programmatic form submissions.
      return is_scalar($input) ? (string) $input : '';
    }
    return NULL;
  }

}
