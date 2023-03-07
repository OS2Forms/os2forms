<?php

namespace Drupal\field_color\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_color_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "field_color_widget_type",
 *   module = "field_color",
 *   label = @Translation("Color"),
 *   field_types = {
 *     "field_color"
 *   }
 * )
 */
class FieldColorWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 22,
      'placeholder' => '',
      'preferredFormat' => 'hex',
      'type_' => 'color',
      'showInput' => 1,
      'showInitial' => 0,
      'allowEmpty' => 1,
      'showAlpha' => 1,
      'disabled' => 0,
      'showButtons' => 1,
      'localStorageKey' => FALSE,
      'showPalette' => 1,
      'showPaletteOnly' => 0,
      'togglePaletteOnly' => 0,
      'showSelectionPalette' => 1,
      'selectionPalette' => '[]',
      'clickoutFiresChange' => 1,
      'hideAfterPaletteSelect' => 0,
      'containerClassName' => '',
      'replacerClassName' => '',
      'cancelText' => 'Cancel',
      'chooseText' => 'Choose',
      'togglePaletteMoreText' => 'More',
      'togglePaletteLessText' => 'Less',
      'clearText' => 'Clear Color Selection',
      'noColorSelectedText' => 'No Color Selected',
      'palette' => '[
        ["#000000","#444444","#5b5b5b","#999999","#bcbcbc","#eeeeee","#f3f6f4","#ffffff"],
        ["#f44336","#744700","#ce7e00","#8fce00","#2986cc","#16537e","#6a329f","#c90076"],
        ["#f4cccc","#fce5cd","#fff2cc","#d9ead3","#d0e0e3","#cfe2f3","#d9d2e9","#ead1dc"],
        ["#ea9999","#f9cb9c","#ffe599","#b6d7a8","#a2c4c9","#9fc5e8","#b4a7d6","#d5a6bd"],
        ["#e06666","#f6b26b","#ffd966","#93c47d","#76a5af","#6fa8dc","#8e7cc3","#c27ba0"],
        ["#cc0000","#e69138","#f1c232","#6aa84f","#45818e","#3d85c6","#674ea7","#a64d79"],
        ["#990000","#b45f06","#bf9000","#38761d","#134f5c","#0b5394","#351c75","#741b47"],
        ["#660000","#783f04","#7f6000","#274e13","#0c343d","#073763","#20124d","#4c1130"]
      ]',
      'maxSelectionSize' => 6,
      'locale' => 'en',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $elements['preferredFormat'] = [
      '#type' => 'select',
      '#title' => $this->t('Preferred Format'),
      '#default_value' => $this->getSetting('preferredFormat'),
      '#options' => [
        'hex' => $this->t('hex'),
        'hex3' => $this->t('hex3'),
        'hsl' => $this->t('hsl'),
        'rgb' => $this->t('rgb'),
        'name' => $this->t('name'),
      ],
    ];
    $elements['type_'] = [
      '#type' => 'select',
      '#title' => $this->t('type'),
      '#default_value' => $this->getSetting('type'),
      '#options' => [
        'color' => $this->t('color'),
        'text' => $this->t('text'),
        'component' => $this->t('component'),
        'float' => $this->t('float'),
      ],
    ];
    $elements['showInput'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Input'),
      '#default_value' => $this->getSetting('showInput'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['showInitial'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Initial'),
      '#default_value' => $this->getSetting('showInitial'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['allowEmpty'] = [
      '#type' => 'select',
      '#title' => $this->t('Allow Empty'),
      '#default_value' => $this->getSetting('allowEmpty'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['showAlpha'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Alpha'),
      '#default_value' => $this->getSetting('showAlpha'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['showButtons'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Buttons'),
      '#default_value' => $this->getSetting('showButtons'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['disabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Disabled'),
      '#default_value' => $this->getSetting('disabled'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['localStorageKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Local Storage Key'),
      '#default_value' => $this->getSetting('localStorageKey'),
    ];
    $elements['showPalette'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Palette'),
      '#default_value' => $this->getSetting('showPalette'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['showPaletteOnly'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Palette Only'),
      '#default_value' => $this->getSetting('showPaletteOnly'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['togglePaletteOnly'] = [
      '#type' => 'select',
      '#title' => $this->t('Toggle Palette Only'),
      '#default_value' => $this->getSetting('togglePaletteOnly'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['showSelectionPalette'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Selection Palette'),
      '#default_value' => $this->getSetting('showSelectionPalette'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['selectionPalette'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Selection Palette'),
      '#default_value' => $this->getSetting('selectionPalette'),
    ];
    $elements['clickoutFiresChange'] = [
      '#type' => 'select',
      '#title' => $this->t('Clickout Fires Change'),
      '#default_value' => $this->getSetting('clickoutFiresChange'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['hideAfterPaletteSelect'] = [
      '#type' => 'select',
      '#title' => $this->t('Hide After Palette Select'),
      '#default_value' => $this->getSetting('hideAfterPaletteSelect'),
      '#options' => [
        0 => $this->t('FALSE'),
        1 => $this->t('TRUE'),
      ],
    ];
    $elements['containerClassName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container Class Name'),
      '#default_value' => $this->getSetting('containerClassName'),
    ];
    $elements['replacerClassName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacer Class Name'),
      '#default_value' => $this->getSetting('replacerClassName'),
    ];
    $elements['cancelText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel Text'),
      '#default_value' => $this->getSetting('cancelText'),
    ];
    $elements['chooseText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Choose Text'),
      '#default_value' => $this->getSetting('chooseText'),
    ];
    $elements['togglePaletteMoreText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toggle Palette More Text'),
      '#default_value' => $this->getSetting('togglePaletteMoreText'),
    ];
    $elements['togglePaletteLessText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toggle Palette Less Text'),
      '#default_value' => $this->getSetting('togglePaletteLessText'),
    ];
    $elements['clearText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clear Text'),
      '#default_value' => $this->getSetting('clearText'),
    ];
    $elements['noColorSelectedText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No Color Selected Text'),
      '#default_value' => $this->getSetting('noColorSelectedText'),
    ];
    $elements['palette'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Palette'),
      '#default_value' => $this->getSetting('palette'),
    ];
    $elements['maxSelectionSize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max Selection Size'),
      '#default_value' => $this->getSetting('maxSelectionSize'),
    ];
    $elements['locale'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locale'),
      '#default_value' => $this->getSetting('locale'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'input_color',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#preferredFormat' => $this->getSetting('preferredFormat'),
      '#showInput' => $this->getSetting('showInput'),
      '#showInitial' => $this->getSetting('showInitial'),
      '#allowEmpty' => $this->getSetting('allowEmpty'),
      '#showAlpha' => $this->getSetting('showAlpha'),
      '#showButtons' => $this->getSetting('showButtons'),
      '#disabled' => $this->getSetting('disabled'),
      '#localStorageKey' => $this->getSetting('localStorageKey'),
      '#showPalette' => $this->getSetting('showPalette'),
      '#showPaletteOnly' => $this->getSetting('showPaletteOnly'),
      '#togglePaletteOnly' => $this->getSetting('togglePaletteOnly'),
      '#showSelectionPalette' => $this->getSetting('showSelectionPalette'),
      '#selectionPalette' => $this->getSetting('selectionPalette'),
      '#clickoutFiresChange' => $this->getSetting('clickoutFiresChange'),
      '#containerClassName' => $this->getSetting('containerClassName'),
      '#replacerClassName' => $this->getSetting('replacerClassName'),
      '#cancelText' => $this->getSetting('cancelText'),
      '#chooseText' => $this->getSetting('chooseText'),
      '#togglePaletteMoreText' => $this->getSetting('togglePaletteMoreText'),
      '#togglePaletteLessText' => $this->getSetting('togglePaletteLessText'),
      '#clearText' => $this->getSetting('clearText'),
      '#noColorSelectedText' => $this->getSetting('noColorSelectedText'),
      '#palette' => $this->getSetting('palette'),
      '#type_' => $this->getSetting('type_'),
      '#maxSelectionSize' => $this->getSetting('maxSelectionSize'),
      '#locale' => $this->getSetting('locale'),
      '#hideAfterPaletteSelect' => $this->getSetting('hideAfterPaletteSelect'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    ];


    return $element;
  }

}
