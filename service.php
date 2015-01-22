<?php

// Read a NEXUS file and generate GeoJSON

require_once (dirname(__FILE__) . '/geojson_tree_drawer.php');

//--------------------------------------------------------------------------------------------------
function display_form()
{
	echo 
'<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" /><style type="text/css" title="text/css">
	body {
		font-family: sans-serif;
		margin:20px;
		}
</style>
<title>NEXUS to GeoJSON</title>
</head>
<body>
<h1>NEXUS to JSON</h1>
<p>Paste in NEXUS tree file containing geographical information</p>
<form method="post" action="service.php">
	<textarea id="nexus" name="nexus" rows="30" cols="60"></textarea><br />
	<input type="submit" value="Go"></input>
</form>
</body>
</html>';

}

//--------------------------------------------------------------------------------------------------
function convert($nexus)
{
	
	if (!preg_match('/^#nexus/i', $nexus))
	{
		// error
		$json = '{"status":"error"}';
	}
	else
	{
		$json = create_geojson_from_nexus($nexus);
	}
	
	echo $json;
	
}

//--------------------------------------------------------------------------------------------------
function main()
{
	$nexus = '';
	
	if (isset($_POST['nexus']))
	{
		$nexus = $_POST['nexus'];
		convert($nexus);
	}
	else
	{
		display_form();
	}
}
	


main();


?>
