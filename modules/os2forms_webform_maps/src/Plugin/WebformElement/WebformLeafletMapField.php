<?php

namespace Drupal\os2forms_webform_maps\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\leaflet\LeafletSettingsElementsTrait;
use Drupal\webform\Plugin\WebformElementBase;

/**
 * Provides a 'webform_map_field' element.
 *
 * @WebformElement(
 *   id = "webform_map_field",
 *   label = @Translation("OS2Forms Kort"),
 *   description = @Translation("Provides a webform map field."),
 *   category = @Translation("OS2Forms"),
 * )
 */
class WebformLeafletMapField extends WebformElementBase {

  use LeafletSettingsElementsTrait;

  // Valid Leaflet control positions (cf.
  // https://github.com/Leaflet/Leaflet/blob/main/src/control/Control.js).
  const string LEAFLET_POSITION_TOP_LEFT = 'topleft';
  const string LEAFLET_POSITION_TOP_RIGHT = 'topright';
  const string LEAFLET_POSITION_BOTTOM_LEFT = 'bottomleft';
  const string LEAFLET_POSITION_BOTTOM_RIGHT = 'bottomright';

  /**
   * {@inheritdoc}
   */
  public function defineDefaultProperties(): array {
    return [
      'mapHeight' => 600,
      'map_layers' => '',
      'lat' => 0,
      'lon' => 0,
      'zoom' => 12,
      'minZoom' => 1,
      'maxZoom' => 18,
      'zoomFiner' => 0,
      'zoomControlPosition' => self::LEAFLET_POSITION_TOP_LEFT,
      'scrollWheelZoom' => 0,
      'doubleClickZoom' => 1,

      'position' => self::LEAFLET_POSITION_TOP_LEFT,
      'marker' => 'defaultMarker',
      'drawPolyline' => 0,
      'drawRectangle' => 0,
      'drawPolygon' => 0,
      'drawCircle' => 0,
      'drawText' => 0,
      'editMode' => 0,
      'dragMode' => 0,
      'cutPolygon' => 0,
      'removalMode' => 0,
      'rotateMode' => 0,

      'polyline_color' => '#3388FF',
      'polyline_intersection' => 0,
      'polyline_error_color' => '#3388FF',
      'polyline_error_message' => '',

      'polygon_color' => '#3388FF',
      'polygon_intersection' => 0,
      'polygon_error_color' => '#3388FF',
      'polygon_error_message' => '',

      'circle_color' => '#3388FF',
      'rectangle_color' => '#3388FF',

    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $map_keys = array_keys(leaflet_map_get_info());

    $positionOptions = [
      self::LEAFLET_POSITION_TOP_LEFT => $this->t('topleft'),
      self::LEAFLET_POSITION_TOP_RIGHT => $this->t('topright'),
      self::LEAFLET_POSITION_BOTTOM_LEFT => $this->t('bottomleft'),
      self::LEAFLET_POSITION_BOTTOM_RIGHT => $this->t('bottomright'),
    ];

    $form['mapstyles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map settings'),
    ];
    $form['mapstyles']['mapstyles_container'] = [
      'mapHeight' => [
        '#title' => $this->t('Map Height'),
        '#type' => 'number',
        '#min' => 1,
        '#description' => $this->t('Note: This can be left empty to make the Map fill its parent container height.'),
      ],
      'map_layers' => [
        '#title' => $this->t('Map Bundles'),
        '#type' => 'select',
        '#options' => array_combine($map_keys, $map_keys),
        '#required' => TRUE,
        '#description' => $this->t('Administer maps and layers <a href="/admin/structure/leaflet_layers" target="_blank">her</a>'),
      ],
      'center' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Map Center'),
        'lat' => [
          '#title' => $this->t('Latitude'),
          '#type' => 'number',
          '#step' => 'any',
          '#size' => 4,
          '#required' => FALSE,
        ],
        'lon' => [
          '#title' => $this->t('Longitude'),
          '#type' => 'number',
          '#step' => 'any',
          '#size' => 4,
          '#required' => FALSE,
        ],
      ],
      'zoom' => [
        '#title' => $this->t('Zoom'),
        '#type' => 'number',
        '#min' => 0,
        '#max' => 22,
        '#required' => TRUE,
        '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
      ],
      'minZoom' => [
        '#title' => $this->t('Min. Zoom'),
        '#type' => 'number',
        '#min' => 0,
        '#max' => 22,
        '#required' => TRUE,
      ],
      'maxZoom' => [
        '#title' => $this->t('Max. Zoom'),
        '#type' => 'number',
        '#min' => 1,
        '#max' => 22,
        '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
        '#required' => TRUE,
      ],
      'zoomFiner' => [
        '#title' => $this->t('Zoom Finer'),
        '#type' => 'number',
        '#max' => 5,
        '#min' => -5,
        '#step' => 1,
        '#description' => $this->t('Value that might/will be added to default Fit Elements Bounds Zoom. (-5 / +5)'),
      ],
      'zoomControlPosition' => [
        '#type' => 'select',
        '#title' => $this->t('Zoom control position'),
        '#options' => $positionOptions,
      ],
      'scrollWheelZoom' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Scroll Wheel Zoom on click'),
        '#description' => $this->t("This option enables zooming by mousewheel as soon as the user clicked on the map."),
      ],
      'doubleClickZoom' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Scroll Wheel Zoom on click'),
        '#description' => $this->t("This option enables zooming by mousewheel as soon as the user clicked on the map."),
      ],
    ];

    $form['toolbar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Leaflet PM Settings'),
    ];
    $form['toolbar']['toolbar_container'] = [
      'position' => [
        '#type' => 'select',
        '#title' => $this->t('Toolbar position.'),
        '#options' => $positionOptions,
      ],
      'marker' => [
        '#type' => 'radios',
        '#title' => $this->t('Marker button.'),
        '#options' => [
          'none' => $this->t('None'),
          'defaultMarker' => $this->t('Default marker'),
          'circleMarker' => $this->t('Circle marker'),
        ],
        '#description' => $this->t('Use <b>Default marker</b> for default Point Marker. In case of <b>Circle marker</b> size can be changed by setting the <em>radius</em> property in <strong>Path Geometries Options</strong> below'),
      ],
      'polyline' => [
        '#type' => 'container',
        'drawPolyline' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Adds button to draw polyline.'),
        ],
        'polyline_options' => [
          '#type' => 'fieldset',
          '#states' => [
            'invisible' => [
              'input[name="properties[drawPolyline]"]' => ['checked' => FALSE],
            ],
          ],
          'polyline_color' => [
            '#type' => 'textfield',
            '#title' => 'Color',
            '#description' => $this->t('Enter value as HEX or CSS color'),
          ],
          'polyline_intersection' => [
            '#type' => 'checkbox',
            '#title' => 'Prevent Intersection',
          ],
          'polyline_error_color' => [
            '#type' => 'textfield',
            '#title' => 'Error color',
            '#description' => $this->t('Enter value as HEX or CSS color'),
            '#states' => [
              'invisible' => [
                'input[name="properties[polyline_intersection]"]' => ['checked' => FALSE],
              ],
            ],
          ],
          'polyline_error_message' => [
            '#type' => 'textfield',
            '#title' => 'Error message',
            '#states' => [
              'invisible' => [
                'input[name="properties[polyline_intersection]"]' => ['checked' => FALSE],
              ],
            ],
          ],
        ],
      ],
      'rectangle' => [
        '#type' => 'container',
        'drawRectangle' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Adds button to draw rectangle.'),
        ],
        'rectangle_options' => [
          '#type' => 'fieldset',
          '#states' => [
            'invisible' => [
              'input[name="properties[drawRectangle]"]' => ['checked' => FALSE],
            ],
          ],
          'rectangle_color' => [
            '#type' => 'textfield',
            '#title' => 'Color',
            '#description' => $this->t('Enter value as HEX or CSS color'),
          ],
        ],
      ],
      'polygon' => [
        '#type' => 'container',
        'drawPolygon' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Adds button to draw polygon.'),
        ],
        'polygon_settings' => [
          '#type' => 'fieldset',
          '#states' => [
            'invisible' => [
              'input[name="properties[drawPolygon]"]' => ['checked' => FALSE],
            ],
          ],
          'polygon_color' => [
            '#type' => 'textfield',
            '#title' => 'Color',
            '#description' => $this->t('Enter value as HEX or CSS color'),
          ],
          'polygon_intersection' => [
            '#type' => 'checkbox',
            '#title' => 'Prevent Intersection',
          ],
          'polygon_error_color' => [
            '#type' => 'textfield',
            '#title' => 'Error color',
            '#states' => [
              'invisible' => [
                'input[name="properties[polygon_intersection]"]' => ['checked' => FALSE],
              ],
            ],
          ],
          'polygon_error_message' => [
            '#type' => 'textfield',
            '#title' => 'Error message',
            '#states' => [
              'invisible' => [
                'input[name="properties[polygon_intersection]"]' => ['checked' => FALSE],
              ],
            ],
          ],
        ],
      ],
      'circle' => [
        '#type' => 'container',
        'drawCircle' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Adds button to draw circle. (unsupported by GeoJSON)'),
          '#disabled' => TRUE,
        ],
        'circle_settings' => [
          '#type' => 'fieldset',
          '#states' => [
            'invisible' => [
              'input[name="properties[drawCircle]"]' => ['checked' => FALSE],
            ],
          ],
          'circle_color' => [
            '#type' => 'textfield',
            '#title' => 'Color',
            '#description' => $this->t('Enter value as HEX or CSS color'),
          ],
        ],
      ],
      'drawText' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Adds button to draw text. (unsupported by GeoJSON)'),
        '#disabled' => TRUE,
      ],
      'editMode' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Adds button to toggle edit mode for all layers.'),
      ],
      'dragMode' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Adds button to toggle drag mode for all layers.'),
      ],
      'cutPolygon' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Adds button to cut hole in polygon.'),
      ],
      'removalMode' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Adds button to remove layers.'),
      ],
      'rotateMode' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Adds button to rotate layers.'),
      ],
    ];

    return $form;
  }

}
