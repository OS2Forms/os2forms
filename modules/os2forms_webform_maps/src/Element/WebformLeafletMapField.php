<?php

namespace Drupal\os2forms_webform_maps\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Element\WebformCompositeFormElementTrait;

/**
 * Provides a webform_map_field.
 *
 * @FormElement("webform_map_field")
 */
class WebformLeafletMapField extends FormElement {

  use WebformCompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformMapElement'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCompositeFormElement'],
      ],
      '#required' => FALSE,
      '#mapHeight' => 600,
      '#lat' => 0,
      '#lon' => 0,
      '#zoom' => 12,
      '#minZoom' => 1,
      '#maxZoom' => 18,
      '#zoomFiner' => 0,
      '#position' => 'topleft',
      '#marker' => 'defaultMarker',
      '#drawPolyline' => 0,
      '#drawRectangle' => 0,
      '#drawPolygon' => 0,
      '#drawCircle' => 0,
      '#drawText' => 0,
      '#editMode' => 0,
      '#dragMode' => 0,
      '#cutPolygon' => 0,
      '#removalMode' => 0,
      '#rotateMode' => 0,
      '#polyline_color' => '#3388FF',
      '#polyline_intersection' => 0,
      '#polyline_error_color' => '#3388FF',
      '#polygon_color' => '#3388FF',
      '#polygon_intersection' => 0,
      '#polygon_error_color' => '#3388FF',
      '#circle_color' => '#3388FF',
      '#rectangle_color' => '#3388FF',
    ];
  }

  /**
   * Expand an email confirm field into two HTML5 email elements.
   */
  public static function processWebformMapElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;
    $map_el = $element['#webform_key'] . '_map';
    $map_info = leaflet_map_get_info();
    $map = $map_info[$element['#map_layers']];
    $map_settings = $map['settings'];
    $json_element_name = 'leaflet-widget-input';
    $reset_title = t('Reset View');
    $locate_title = t('Locate my position');

    $feature = [];
    $map['id'] = $map_el;
    $map['geofield_cardinality'] = 1;
    $map['settings'] = [
      'dragging' => $map_settings['dragging'],
      'touchZoom' => $map_settings['touchZoom'],
      'scrollWheelZoom' => $element['#scrollWheelZoom'] ?? $map_settings['scrollWheelZoom'],
      'doubleClickZoom' => $element['#doubleClickZoom'] ?? $map_settings['doubleClickZoom'],
      'zoomControl' => $map_settings['zoomControl'],
      'attributionControl' => $map_settings['attributionControl'],
      'trackResize' => $map_settings['trackResize'],
      'fadeAnimation' => $map_settings['fadeAnimation'],
      'zoomAnimation' => $map_settings['zoomAnimation'],
      'closePopupOnClick' => $map_settings['closePopupOnClick'],
      'layerControl' => TRUE,
      'map_position_force' => FALSE,
      'zoom' => $element['#zoom'],
      'zoomFiner' => $element['#zoomFiner'],
      'minZoom' => $element['#minZoom'],
      'maxZoom' => $element['#maxZoom'],
      'center' => [
        'lat' => (float) $element['#lat'],
        'lon' => (float) $element['#lon'],
      ],
      'path' => '{"color":"#3388ff","opacity":"1.0","stroke":true,"weight":3,"fill":"depends","fillColor":"*","fillOpacity":"0.2","radius":"6"}',
      'leaflet_markercluster' => [
        'control' => FALSE,
        'options' => '{"spiderfyOnMaxZoom":true,"showCoverageOnHover":true,"removeOutsideVisibleBounds": false}',
        'excluded' => FALSE,
        'include_path' => FALSE,
      ],
      'gestureHandling' => FALSE,
      'reset_map' => [
        'control' => FALSE,
        'options' => '{"position":"' . $element['#position'] . '","title":"' . $reset_title . '"}',
      ],
      'locate' => [
        'control' => TRUE,
        'options' => '{"position":"' . $element['#position'] . '","setView":"untilPanOrZoom","returnToPrevBounds":true,"keepCurrentZoomLevel":true,"strings":{"title":"' . $locate_title . '"}}',
        'automatic' => FALSE,
      ],
    ];
    $map['context'] = 'widget';

    /** @var \Drupal\leaflet\LeafletService $leaflet_service */
    $leaflet_service = \Drupal::service('leaflet.service');
    $element['map'] = $leaflet_service->leafletRenderMap($map, $feature, $element['#mapHeight'] . 'px');

    $leaflet_widget_toolbar = [
      'position' => $element['#position'],
      'marker' => $element['#marker'],
      'drawPolyline' => (bool) $element['#drawPolyline'],
      'drawRectangle' => (bool) $element['#drawRectangle'],
      'drawPolygon' => (bool) $element['#drawPolygon'],
      'drawCircle' => (bool) $element['#drawCircle'],
      'drawText' => (bool) $element['#drawText'],
      'editMode' => (bool) $element['#editMode'],
      'dragMode' => (bool) $element['#dragMode'],
      'cutPolygon' => (bool) $element['#cutPolygon'],
      'removalMode' => (bool) $element['#removalMode'],
      'rotateMode' => (bool) $element['#rotateMode'],
    ];

    $leaflet_widget_js_settings = [
      'map_id' => $element['map']['#map_id'],
      'jsonElement' => '.' . $json_element_name,
      'multiple' => TRUE,
      'cardinality' => 0,
      'autoCenter' => 1,
      'inputHidden' => TRUE,
      'inputReadonly' => TRUE,
      'toolbarSettings' => $leaflet_widget_toolbar,
      'scrollZoomEnabled' => 1,
      'map_position' => $map_settings['map_position'] ?? [],
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId() ?? 'da',
    ];

    $element['map']['#attached']['drupalSettings']['leaflet'][$map_el]['map_settings'] = $element;
    $element['map']['#attached']['drupalSettings']['leaflet'][$map_el]['leaflet_widget'] = $leaflet_widget_js_settings;
    $element['map']['#attached']['library'][] = 'os2forms_webform_maps/webformmap';
    $element['map']['#attached']['library'][] = 'os2forms_webform_maps/webform_leaflet';

    $element['mail_2'] = [
      '#type' => 'textarea',
      '#webform_element' => TRUE,
      '#title' => t('GeoJson Data'),

    ];
    $element['mail_2']['#attributes']['class'][] = $json_element_name;

    $element['image_data'] = [
      '#type' => 'textarea',
      '#webform_element' => TRUE,
      '#title' => t('Image Data'),

    ];
    $element['image_data']['#attributes']['class'][] = 'leaflet-widget-image';

    // Initialize the mail elements to allow for webform enhancements.
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_manager->buildElement($element['mail_2'], $complete_form, $form_state);
    $element_manager->buildElement($element['image_data'], $complete_form, $form_state);

    // Don't require the main element.
    $element['#required'] = FALSE;

    // Hide title and description from being display.
    $element['#title_display'] = 'invisible';
    $element['#description_display'] = 'invisible';

    // Remove properties that are being applied to the sub elements.
    unset(
      $element['#attributes'],
      $element['#description'],
      $element['#help'],
      $element['#help_title'],
      $element['#help_display']
    );

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [
      get_called_class(),
      'validateWebformMapElement',
    ]);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        $element['#default_value'] = '';
      }
      return [
        'mail_2' => $element['#default_value'],
        'image_data' => $element['#default_value'],
      ];
    }

    return $input;
  }

  /**
   * Validates an email confirm element.
   */
  public static function validateWebformMapElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $mail_element = &$element;

    $mail_2 = trim($mail_element['mail_2']['#value']);
    $image_data = trim($mail_element['image_data']['#value']);
    $result = !empty($mail_2) ? json_encode([
      'geojson' => $mail_2,
      'image' => $image_data,
    ]) : $mail_2;

    // Field must be converted from a two-element array into a single
    // string regardless of validation results.
    $form_state->setValueForElement($mail_element['mail_2'], NULL);
    $form_state->setValueForElement($mail_element['image_data'], NULL);

    $element['#value'] = $result;
    $form_state->setValueForElement($element, $result);
  }

}
