# OS2Forms Webform Maps module for Drupal 9

## Module description

Provides integration with Leaflet maps and provides map element for webform.

## How does it work

The module provides a new element type for webform. The element type is called "OS2Forms Kort".
The element type is based on the Leaflet library. The element type provides a map with a marker
that can be moved around on the map. The element type also provides ways of changing layers on
the map. The data can be exported to PDF.

## Installation

The module can be installed using the standard Drupal installation procedure.

## Fetching GeoJSON using the API

Assume that we have a webform with ID `my_webform` and the webform has a Map element with ID `my_map`. We can then fetch
the data for a submission with UUID `c34d01c5-7bd9-4b15-8b01-5787959453c0` on the webform with a HTTP `GET` request (cf.
[OS2Forms REST API](https://github.com/OS2Forms/os2forms_rest_api/blob/main/README.md)):

``` shell name=api-fetch-submission-data
curl --silent --location --header 'api-key: my-api-key' http://127.0.0.1:8000/webform_rest/my_webform/submission/c34d01c5-7bd9-4b15-8b01-5787959453c0
# {"entity":{"serial":[{"value":1}],"sid":[{"value":2}],"uuid":[{"value":"c34d01c5-7bd9-4b15-8b01-5787959453c0"}],…```
```

The result, however, contains a lot of data and we need a little more work the extract just the GeoJSON data.

Using [`jq`](https://jqlang.org/) (or similar tools), we can drill down into the full submission data to extract just
the GeoJSON data from the Map element (JSON decoding twice along the way):

``` shell name=api-extract-map-element-data
curl --silent --location --header 'api-key: my-api-key' http://127.0.0.1:8000/webform_rest/my_webform/submission/c34d01c5-7bd9-4b15-8b01-5787959453c0 | jq '.data.my_map | fromjson | .geojson | fromjson'
```

The final result will be something like

``` json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {},
      "geometry": {
        "type": "LineString",
        "coordinates": [
          [
            10.193674,
            56.158929
          ],
          [
            10.241758,
            56.155296
          ],
          [
            10.242788,
            56.130048
          ],
          [
            10.189209,
            56.129283
          ]
        ]
      }
    }
  ]
}
```
