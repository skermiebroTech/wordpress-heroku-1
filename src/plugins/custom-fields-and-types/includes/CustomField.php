<?php

class CustomField
{
  static $types = array(
    'text', 'date', 'time', 'textarea', 'select', 'route', 'number'
  );

  public function __construct($config)
  {
    $this->name = $config['name'];
    $this->key = $config['key'];
    $this->type = $config['type'];
    $this->options = @$config['options'];
    $this->show_in_rest = boolval(@$config['show_in_rest']);
    $this->required = boolval(@$config['required']);
    $this->value = !empty($config['value']) ? $config['value'] : null;
  }

  public function setValue($value)
  {
    $this->value = $value;
  }

  public function register($types)
  {
    foreach ($types as $type):
      /** @link https://developer.wordpress.org/reference/functions/register_meta/ */
      register_meta('post', $this->key, array(
        'object_subtype' => $type,
        'type' => $this->getMetaType(), // TODO: (Extend types) 'string', 'boolean', 'integer', 'number', 'array', and 'object'.
        'description' => "Field {$this->name}",
        'single' => true, // TODO: Extend for repeateable fields
        // 'sanitize_callback' => function() {},
        // 'auth_callback' => function() {},
        'show_in_rest' => $this->getShowInRest()
      ));
    endforeach;
  }

  public function getShowInRest()
  {
    switch ($this->type) {
      case 'route': return array(
        'schema' => array(
          'items' => array(
            'type' => 'object',
            'properties' => array(
              'latitude' => array('type' => 'number'),
              'longitude' => array('type' => 'number')
            ),
          )
        )
      );
      default: return $this->show_in_rest;
    }
  }

  public function getMetaType()
  {
    switch ($this->type) {
      case 'route': return 'array';
      case 'number': return 'number';
      default: return 'string';
    }
  }

  public function render()
  {
    switch ($this->type) {
      case 'text': return $this->renderText();
      case 'date': return $this->renderDate();
      case 'time': return $this->renderTime();
      case 'textarea': return $this->renderTextarea();
      case 'select': return $this->renderSelect();
      case 'number': return $this->renderNumber();
      case 'route': return $this->renderRoute();
      default: return $this->renderText();
    }
  }

  public function getRequiredAttr()
  {
    if ($this->required) {
      return 'required';
    }
  }

  public function renderText() { ?>
    <div style="margin-bottom: 5px">
      <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
      <input style="width: 100%" id="<?= $this->key; ?>" class="form-control" type="text" name="<?= $this->key; ?>" value="<?= $this->value; ?>" <?= $this->getRequiredAttr(); ?>>
    </div><hr>
  <?php }

  public function renderDate() { ?>
    <div style="margin-bottom: 5px">
      <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
      <input style="width: 100%" id="<?= $this->key; ?>" class="form-control" type="date" name="<?= $this->key; ?>" value="<?= $this->value; ?>" <?= $this->getRequiredAttr(); ?>>
    </div><hr>
  <?php }

  public function renderTime() { ?>
    <div style="margin-bottom: 5px">
      <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
      <input style="width: 100%" id="<?= $this->key; ?>" class="form-control" type="time" name="<?= $this->key; ?>" value="<?= $this->value; ?>" <?= $this->getRequiredAttr(); ?>>
    </div><hr>
  <?php }

  public function renderTextarea() { ?>
    <div style="margin-bottom: 5px">
      <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
      <textarea style="width: 100%" id="<?= $this->key; ?>" class="form-control" name="<?= $this->key; ?>" <?= $this->getRequiredAttr(); ?>><?= $this->value; ?></textarea>
    </div><hr>
  <?php }

  public function renderSelect() { ?>
    <div style="margin-bottom: 5px">
      <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
      <select style="width: 100%" class="form-control" name="<?= $this->key; ?>" <?= $this->getRequiredAttr(); ?>>
        <?php foreach (explode("\n", $this->options) as $option): ?>
          <option <?= $this->value === $option ? 'selected' : ''; ?> value="<?= $option; ?>"><?= $option; ?></option>
        <?php endforeach; ?>
      </select>
    </div><hr>
  <?php }

  public function renderNumber() { ?>
    <div style="margin-bottom: 5px">
      <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
      <input style="width: 100%" id="<?= $this->key; ?>" type="number" name="<?= $this->key; ?>" value="<?= $this->value; ?>" <?= $this->getRequiredAttr(); ?>>
    </div><hr>
  <?php }

  public function renderRoute() { ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= $this->options; ?>&callback=initMap&libraries=places"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/geocomplete/1.7.0/jquery.geocomplete.min.js"></script>
    <script type="text/javascript">
    function initMap() {
      var $points = jQuery('#points-<?= $this->key; ?>');
      var $input = jQuery('#input-<?= $this->key; ?>');
      var $search = jQuery('#search-<?= $this->key; ?>').geocomplete({
        location: 'Canary Islands',
        map: '#map-<?= $this->key; ?>',
        mapOptions: {
          mapTypeId: 'satellite'
        }
      });

      var map = $search.geocomplete('map');
      var markers = [];
      var polyline = new google.maps.Polyline({
        path: [],
        geodesic: true,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 2
      });

      polyline.setMap(map);

      map.addListener('click', function (event) {
        createMarker(event);
        handlePoints();
      });

      window.deleteMarker = function (index) {
        markers[index].setMap(null);
        markers.splice(index, 1);
        handlePoints();
      }

      // create marker on map
      function createMarker(data) {
        var marker = new google.maps.Marker({
          position: data.latLng,
          draggable: true,
          animation: google.maps.Animation.DROP,
          label: markers.length + 1 + '',
          icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
          map: map
        });

        marker.addListener('dragend', function (event) {
          handlePoints();
        });

        markers.push(marker);
      }

      function toRad(v){return v * Math.PI / 180;}
      function haversine(l1, l2) {
        var R = 6371; // km
        var x1 = l2.latitude-l1.latitude;
        var dLat = toRad(x1);
        var x2 = l2.longitude-l1.longitude;
        var dLon = toRad(x2);
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(toRad(l1.latitude)) * Math.cos(toRad(l2.latitude)) *
        Math.sin(dLon/2) * Math.sin(dLon/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        var d = R * c;
        return d;
      }

      // Draw markers on screen and add to hidden input
      function handlePoints () {
        $points.empty();
        var totalDistanceInKM = 0;

        markers.map(function(marker, index) {
          var lat = marker.position.lat();
          var lng = marker.position.lng();

          if (markers[index + 1]) {
            totalDistanceInKM += haversine({
              latitude: lat,
              longitude: lng
            }, {
              latitude: markers[index + 1].position.lat(),
              longitude: markers[index + 1].position.lng(),
            });
          }

          $points.append([
            '<div style="padding: 15px">',
              '<input type="hidden" name="<?= $this->key; ?>[' + index + '][latitude]" value="' + lat + '">',
              '<input type="hidden" name="<?= $this->key; ?>[' + index + '][longitude]" value="' + lng + '">',
              '<span style="cursor: pointer" class="dashicons dashicons-trash" onclick="deleteMarker(' + index + ')"></span>',
              '<b>Point ' + (index + 1) + '</b><br>',
               'Lat: '+ lat + '. Lng:' + lng,
            '</div>'
          ].join(''));
        });

        $points.append('<div>Total: <b>' + totalDistanceInKM.toFixed(2) + 'km<b></div>');

        polyline.setPath(markers.map(function(marker) {
          return { lat: marker.position.lat(), lng: marker.position.lng() };
        }));
      }

      <?php if (!empty($this->value)): ?>
        var oldMarkers = <?= json_encode(unserialize($this->value)); ?>;

        oldMarkers.map(function (marker, index) {
          createMarker({
            latLng: new google.maps.LatLng(marker.latitude, marker.longitude)
          });
        });

        handlePoints();

        setTimeout(function() {
          map.setCenter(new google.maps.LatLng(oldMarkers[0].latitude, oldMarkers[0].longitude));
          map.setZoom(14);
        }, 1000);
      <?php endif; ?>
    }
    </script>
    <label for="<?= $this->key; ?>"><?= $this->name; ?></label>
    <div style="display: flex">
      <div style="flex: 1">
        <input id="search-<?= $this->key; ?>" type="text" style="width: 100%; border-radius: 0;">
        <div id="results-<?= $this->key; ?>"></div>
        <div id="map-<?= $this->key; ?>" style="height: 500px; width: 100%"></div>
      </div>
      <div id="points-<?= $this->key; ?>" style="flex: 0.5; padding: 15px; background: #eee;">
        <h2>Click on the map to set a point</h2>
        <p>Search for the main location on the text input to center the map</p>
        <p>Use the zoom arrows to get closer</p>
        <p>You can drag and drop points to change their locations.</p>
      </div>
    </div><hr>
  <?php }
}
