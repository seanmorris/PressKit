<?php
namespace SeanMorris\PressKit\View;
class Map extends \SeanMorris\Theme\View
{
}
__halt_compiler(); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<title>Markercluster with Mapbox marker data</title>
<meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
<script src='https://api.mapbox.com/mapbox.js/v3.1.1/mapbox.js'></script>
<link href='https://api.mapbox.com/mapbox.js/v3.1.1/mapbox.css' rel='stylesheet' />
<style>
  body { margin:0; padding:0; }
  #map { position:absolute; top:0; bottom:0; width:100%; }
</style>
</head>
<body>
<script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/leaflet.markercluster.js'></script>
<link href='https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/MarkerCluster.css' rel='stylesheet' />
<link href='https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/MarkerCluster.Default.css' rel='stylesheet' />

<div id='map'></div>

<script>
L.mapbox.accessToken = 'pk.eyJ1Ijoic2Vhbm1vcnJpcyIsImEiOiJjajNhZ3k0a3QwMHZ1MndvOTk4d3Rldzl5In0.4Se2eSBNVPnodiR1yYCfpA';
// Here we don't use the second argument to map, since that would automatically
// load in non-clustered markers from the layer. Instead we add just the
// backing tileLayer, and then use the featureLayer only for its data.
var map = L.mapbox.map('map')
  .setView([40.73, -74.011], 13)
  //.addLayer(L.mapbox.tileLayer('mapbox.streets'))
  .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/dark-v9'));


// Since featureLayer is an asynchronous method, we use the `.on('ready'`
// call to only use its marker data once we know it is actually loaded.
L.mapbox.featureLayer('/scaffold/mapData').on('ready', function(e) {
    // The clusterGroup gets each marker in the group added to it
    // once loaded, and then is added to the map
    var clusterGroup = new L.MarkerClusterGroup();
    e.target.eachLayer(function(layer) {
        clusterGroup.addLayer(layer);
    });
    map.addLayer(clusterGroup);
});
</script>
</body>
</html>