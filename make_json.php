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

echo $json;




?>
