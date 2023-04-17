(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.webformMap = {
    attach: function (context, settings, drupalSettings) {
      $.each(settings.leaflet, function (map_id, settings) {
        $('#' + map_id, context).each(function () {
          let map_container = $(this);

          map_container.data('leaflet').lMap.pm.Draw.Circle.setPathOptions({
            color: settings.map_settings['#circle_color']
          });
          map_container.data('leaflet').lMap.pm.Draw.Line.setPathOptions({
            color: settings.map_settings['#polyline_color'],
            templineStyle: settings.map_settings['#polyline_color'],
            hintlineStyle: settings.map_settings['#polyline_color']
          });
          map_container.data('leaflet').lMap.pm.Draw.Polygon.setPathOptions({color: settings.map_settings['#polygon_color']});
          map_container.data('leaflet').lMap.pm.Draw.Rectangle.setPathOptions({color: settings.map_settings['#rectangle_color']});
        });
      });

      $("input.os2forms-dawa-address", context).bind('autocompleteclose', function (event, node) {
          let address = $(this).val();
          $.get(location.protocol +
            '//api.dataforsyningen.dk/adresser?q=' + address + '&format=json&struktur=mini',
            function (data) {
              if (data[0]) {
                $.each(settings.leaflet, function (map_id, settings) {
                  $('#' + map_id, context).each(function () {
                    let map_container = $(this);
                    map_container.data('leaflet').lMap.panTo(new L.LatLng(data[0]['y'], data[0]['x']));
                  });
                });
              }
            });
        });
    }
  };

})
(jQuery, Drupal, drupalSettings);
