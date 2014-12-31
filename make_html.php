<?php

// Read a NEXUS file and generate GeoJSON

require_once ('geojson_tree_drawer.php');

$filename = '';
if ($argc < 2)
{
	echo "Usage: make_json.php <NEXUS file> \n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$file = @fopen($filename, "r") or die("couldn't open $filename");
fclose($file);


$json = create_geojson($filename);


$template = <<<EOT
<html>
<head>
	<meta charset="utf-8" /> 
	<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
	<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
</head>
<body>
	<div id="map" style="width: 100%; height: 100%"></div>
	<script>
	
		function onEachFeature(feature, layer) {
			// does this feature have a property named popupContent?
			if (feature.properties && feature.properties.popupContent) {
				layer.bindPopup(feature.properties.popupContent);
			}
		}	

	    var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
				'<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery © <a href="http://mapbox.com">Mapbox</a>',
			mbUrl = 'https://{s}.tiles.mapbox.com/v3/{id}/{z}/{x}/{y}.png';

	    var grayscale   = L.tileLayer(mbUrl, {id: 'examples.map-20v6611k', attribution: mbAttr}),
		    streets  = L.tileLayer(mbUrl, {id: 'examples.map-i875mjb7',   attribution: mbAttr});

		var map = L.map('map', { center: [0,0], zoom: 10, layers: [grayscale, streets]} );
		
		var baseLayers = {
			"Grayscale": grayscale,
			"Streets": streets
		};		
		
		L.control.layers(baseLayers).addTo(map);

		L.tileLayer('https://{s}.tiles.mapbox.com/v3/{id}/{z}/{x}/{y}.png', {
			maxZoom: 18,
			attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
				'<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery © <a href="http://mapbox.com">Mapbox</a>',
			id: 'examples.map-i875mjb7'
		}).addTo(map);


		var data = <GEOJSON>;

		L.geoJson(data, { 
			style: function (feature) {
				return feature.properties && feature.properties.style;
			},
			onEachFeature: onEachFeature,
			}).addTo(map);
			
       var southWest = L.latLng(data.bbox[1], data.bbox[0]),
    	northEast = L.latLng(data.bbox[3], data.bbox[2]),
    	bounds = L.latLngBounds(southWest, northEast);	
    	map.fitBounds(bounds);
	</script>

</body>
</html>
EOT;

$template = str_replace('<GEOJSON>', $json, $template);

echo $template;

?>