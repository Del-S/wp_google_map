// Based on http://stackoverflow.com/questions/10898279/google-maps-v3-imagemaptype-prevent-wrapping from Tina CG Hoehr
var uploadUri = gimSettings.upload_uri;
var mapName = gimSettings.map_name;
var icons = gimSettings.markers;
var developerMode = gimSettings.developer_mode;
var marker_redirect = gimSettings.marker_redirect;

var divHeight = document.getElementById("map").clientHeight;

var TILE_SIZE = gimSettings.tile_size;

var map;
var allowedBounds;

function initMap() {
    var mapOptions = {
        center: {lat: 0, lng: 0},
        zoom: 1,
        inertia: false,
        streetViewControl: false,
        mapTypeControlOptions: {
            mapTypeIds: ['image']
        }
    };   
    
    var imageTypeOptions = {
        getTileUrl: function(coord, zoom) {
            var normalizedCoord = getNormalizedCoord(coord, zoom);
            if (!normalizedCoord) {
                return null;
            }
            var bound = Math.pow(2, zoom);
            return uploadUri +
                '/' + zoom + '/' + normalizedCoord.x + '/' +  
                (bound - normalizedCoord.y - 1) + '.jpg';
        },
        tileSize: new google.maps.Size(256, 256),
        maxZoom: 3,
        minZoom: 1,
        radius: 100,
        name: mapName
    };
    var imageMapType = new google.maps.ImageMapType(imageTypeOptions);

    map = new google.maps.Map(document.getElementById('map'), mapOptions);
    map.mapTypes.set('image', imageMapType);
    map.setMapTypeId('image');

    /* Map listeners */
    google.maps.event.addListener(map, 'tilesloaded', function() {
        updateEdge();
    });

    google.maps.event.addListener(map, 'zoom_changed', function() {
        updateEdge();
        boxIn();
    });
    
    // There could be problems with 'center_changed' => then use 'idle' instead. Don't use 'dragend' because of drag momentum.
    google.maps.event.addListener(map, 'center_changed', function() {
        boxIn();
    });

    if(developerMode) {
        google.maps.event.addListener(map, 'click', function(e) {
            console.log("map clicked at: " + e.latLng.lat() + "," + e.latLng.lng());
        });
    }

    updateEdge();

    for (var k in icons) {
        if (icons.hasOwnProperty(k)) {
            var ico = icons[k];
            addIcon(ico['lat'],ico['long'],ico['img_link'], ico["link"]); 
        }
    }
}

// https://developers.google.com/maps/documentation/javascript/examples/map-coordinates
function degreesToRadians(deg) {
    return deg * (Math.PI / 180);
}

function radiansToDegrees(rad) {
    return rad / (Math.PI / 180);
}

function bound(value, opt_min, opt_max) {
    if (opt_min != null) value = Math.max(value, opt_min);
    if (opt_max != null) value = Math.min(value, opt_max);
    return value;
}

function fromLatLngToPoint(latLng, map) {
    var point = new google.maps.Point(0, 0);
    var origin = new google.maps.Point(TILE_SIZE/2, TILE_SIZE/2);

    var pixelsPerLonDegree_ = TILE_SIZE / 360;
    var pixelsPerLonRadian_ = TILE_SIZE / (2 * Math.PI);

    point.x = origin.x + latLng.lng() * pixelsPerLonDegree_;
    
    var siny = bound(Math.sin(degreesToRadians(latLng.lat())), -0.9999, 0.9999);
    point.y = origin.y + 0.5 * Math.log((1 + siny) / (1 - siny)) * -pixelsPerLonRadian_;
    return point;
}

function fromPointToLatLng(point) {
    var pixelOrigin_ = new google.maps.Point(TILE_SIZE / 2, TILE_SIZE / 2);
    var origin = new google.maps.Point(TILE_SIZE/2, TILE_SIZE/2);

    var pixelsPerLonDegree_ = TILE_SIZE / 360;
    var pixelsPerLonRadian_ = TILE_SIZE / (2 * Math.PI);

    var origin = pixelOrigin_;
    var lng = (point.x - origin.x) / pixelsPerLonDegree_;
    var latRadians = (point.y - origin.y) / -pixelsPerLonRadian_;
    var lat = radiansToDegrees(2 * Math.atan(Math.exp(latRadians)) - Math.PI / 2);
    return new google.maps.LatLng(lat, lng);
};

function midpointLat() {
    var tileFactor = 1 << map.getZoom();
    var midpointFromTop = divHeight / tileFactor / 2;
    return fromPointToLatLng(new google.maps.Point(0, midpointFromTop)).lat();
}

function addMarker(lat, lng) {
    new google.maps.Marker({
        position: new google.maps.LatLng(lat, lng),
    }).setMap(map);
}

function addIcon(lat, lng, url, link) {
    var marker = new google.maps.Marker({
        position: new google.maps.LatLng(lat, lng),
        icon: url,
    });
    if(marker_redirect) {
        marker.addListener('click', function() {
            console.log(link);
        });
    }
    marker.setMap(map);
}

function updateEdge() {
    var bounds = map.getBounds();
    if(bounds == undefined) { return; }
    var sw = bounds.getSouthWest();
    var ne = bounds.getNorthEast();

    var swLng = sw.lng();
    var swLat = sw.lat();

    var neLng = ne.lng();
    var neLat = ne.lat();

    if (swLng > neLng) {
        swLng -= 360;
    } 
    var width = neLng - swLng;

    var left = Math.min(-180 + (width/2), -0.000001);
    var right = Math.max(180 - (width/2), 0.000001);

    var divCenterLat = fromPointToLatLng(new google.maps.Point(0, divHeight)).lat();
    var currentZoom = map.getZoom();

    var top = midpointLat();
    var bottom = -midpointLat();

    allowedBounds = new google.maps.LatLngBounds(
    new google.maps.LatLng(bottom,left),
    new google.maps.LatLng(top,right));
}

function boxIn() {
    if (allowedBounds.contains(map.getCenter())) {
        return;
    } else {
        var mapCenter = map.getCenter();
        var X = mapCenter.lng();
        var Y = mapCenter.lat();

        var AmaxX = allowedBounds.getNorthEast().lng();
        var AmaxY = allowedBounds.getNorthEast().lat();
        var AminX = allowedBounds.getSouthWest().lng();
        var AminY = allowedBounds.getSouthWest().lat();

        if (X < AminX) {
            X = AminX;
        }
        if (X > AmaxX) {
            X = AmaxX;
        }
        if (Y < AminY) {
            Y = AminY;
        }
        if (Y > AmaxY) {
            Y = AmaxY;
        }

        map.setCenter(new google.maps.LatLng(Y, X));
    }
}

// Normalizes the coords that tiles repeat across the x axis (horizontally)
// like the standard Google map tiles.
function getNormalizedCoord(coord, zoom) {
    var y = coord.y;
    var x = coord.x;

    // tile range in one direction range is dependent on zoom level
    // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
    var tileRange = 1 << zoom;

    // don't repeat across y-axis (vertically)
    if (y < 0 || y >= tileRange) {
        return null;
    }

    if (x < 0 || x >= tileRange) {
        // x = (x % tileRange + tileRange) % tileRange;
        return null;
    }

    return {x: x, y: y};
}