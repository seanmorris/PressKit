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
<title>HyperMap</title>
<meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
<script src='https://api.mapbox.com/mapbox.js/v3.1.1/mapbox.js'></script>
<script src='/SeanMorris/PortfolioSite/jquery-1.11.2.min.js'></script>
<link href='https://api.mapbox.com/mapbox.js/v3.1.1/mapbox.css' rel='stylesheet' />
<style>
	html, body {
		margin:0; padding:0;
		height: 100%;
		background: #222;
	}
	.container {
		display: flex;
		flex-direction: row;
		height: 100%;
	}
	#map {
		flex-grow: 1;
	}
	.sidebar {
		display: flex;
		flex-direction: column;
		width:120px;
		background: #000;
		border-right: 1px solid #333;
		padding-top: 120px;
	}
	.sidebar div {
		padding:15px;
		display: flex;
		justify-content: center;
		flex-direction: column;
		text-align: center; 
		font-family: arial;
		text-transform: uppercase;
		font-size: 8pt;
		color: #FFF;
		border-bottom: 1px solid #333;
	}
	.sidebar div:first-child {
		border-top: 1px solid #333;
	}
	.sidebar div:hover {
		background: #222;
	}

	img.logo {
		position: absolute;
		top: -20px;
		left: 10px;
		z-index: 999;
	}

	div.loader{
		position: absolute;
		top: 0px;
		left: 0px;
		width: 100%;
		height: 100%;
		background: rgba(255,255,255,0.5);
		z-index: 99999999999999999;    
		display: none;
	}
	div.loader img{
		margin-left:50%;
		margin-top:50%;
		transform: translate(-50%, -50%);
	}
	div.activeButton {
		background: #444;
	}
</style>
</head>
<body>
<script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/leaflet.markercluster.js'></script>
<link href='https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/MarkerCluster.css' rel='stylesheet' />
<link href='https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/MarkerCluster.Default.css' rel='stylesheet' />

<img class = "logo" src = "/rabbitLogo.png" />

<div class = "loader">
	<img src = "/coffee-cup-loading.gif" />
</div>

<div class = "container">
	<div class = "sidebar">
		<div class = "loaderButton" data-map-points = 'y_test'>Yoga</div>
		<div class = "loaderButton" data-map-points = 'psy_test'>Psychotherapy</div>
		<div class = "loaderButton" data-map-points = 'acc_test'>Acupuncture</div>
		<div>
			Zipcode: <br /> <br />
			<input class = "location" type = "text" />
			<br />
			<input type = "button" value = "Search" />
		</div>
	</div>
	<div id='map'></div>
</div>

<script>
L.mapbox.accessToken = 'pk.eyJ1Ijoic2Vhbm1vcnJpcyIsImEiOiJjajNhZ3k0a3QwMHZ1MndvOTk4d3Rldzl5In0.4Se2eSBNVPnodiR1yYCfpA';
// Here we don't use the second argument to map, since that would automatically
// load in non-clustered markers from the layer. Instead we add just the
// backing tileLayer, and then use the featureLayer only for its data.
var map = L.mapbox.map('map')
	.setView([40.73, -74.011], 13)
	.addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/dark-v9'));

var layers = {};
$(function()
{
	var loader = $('.loader');
	$('.loaderButton').click(function()
	{
		loader.fadeIn();
		$('.loaderButton').removeClass('activeButton');
		$(this).addClass('activeButton');

		var pointSetName = $(this).attr('data-map-points');
		
		// Since featureLayer is an asynchronous method, we use the `.on('ready'`
		// call to only use its marker data once we know it is actually loaded.
		L.mapbox.featureLayer('/scaffold/mapData/' + pointSetName).on('ready', function(e)
		{
				// The clusterGroup gets each marker in the group added to it
				// once loaded, and then is added to the map
				var clusterGroup = new L.MarkerClusterGroup();

				map.eachLayer(function(layer){
					map.removeLayer(layer);
				});
				
				e.target.eachLayer(function(layer)
				{
						clusterGroup.addLayer(layer);
				});

				map.addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/dark-v9'));
				map.addLayer(clusterGroup);

				loader.fadeOut();
		});

	})
});
</script>
</body>
</html>