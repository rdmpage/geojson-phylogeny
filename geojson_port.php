<?php

// 

require_once(dirname(__FILE__) . '/port.php');

//--------------------------------------------------------------------------------------------------
// Based on http://stackoverflow.com/a/2706847/9684
class GoogleMapsAPIProjection
{
	var $PixelTileSize = 256.0;
	var $DegreesToRadiansRatio ;
	var $RadiansToDegreesRatio;
	
	var $PixelGlobeCenter = array();
	
	var $XPixelsToDegreesRatio;
	var $YPixelsToRadiansRatio;
	
	function __construct($zoomLevel = 0)
	{
		$this->DegreesToRadiansRatio = 180.0 / M_PI;
		$this->RadiansToDegreesRatio = M_PI / 180.0;
	
		$this->pixelGlobeSize = $this->PixelTileSize * pow(2, $zoomLevel);
		$this->XPixelsToDegreesRatio = $this->pixelGlobeSize / 360.0;
		$this->YPixelsToRadiansRatio = $this->pixelGlobeSize / (2.0 * M_PI);
		$halfPixelGlobeSize = $this->pixelGlobeSize / 2.0;
		
		$this->PixelGlobeCenter = array($halfPixelGlobeSize,$halfPixelGlobeSize);
	}
		
	// convert (long, lat) pair to pixels
	function FromCoordinatesToPixel($coordinates)
	{
		$x = $this->PixelGlobeCenter[0] + ($coordinates[0] * $this->XPixelsToDegreesRatio);
		$f = min(max(sin($coordinates[1] * $this->RadiansToDegreesRatio), -0.9999), 0.9999);
		$y = $this->PixelGlobeCenter[1] + 0.5 * (log( (1.0 + $f) / (1.0 - $f)) * -$this->YPixelsToRadiansRatio);
		$pixel = array($x, $y);		
		return $pixel;
	}

	// convert (x,y) to (long, lat)
	function FromPixelToCoordinates($pixel)
	{
		$longitude = ($pixel[0] - $this->PixelGlobeCenter[0]) / $this->XPixelsToDegreesRatio;
		
		$latitude = (2 * atan( exp( 
			($pixel[1] - $this->PixelGlobeCenter[1]) / -$this->YPixelsToRadiansRatio))
			- M_PI / 2.0) * $this->DegreesToRadiansRatio;
		
		$longlat = array($longitude, $latitude);
		return $longlat;
	}
	
}

//--------------------------------------------------------------------------------------------------
// Bounding box
class BoundingBox
{
	var $min_xy;
	var $max_xy;
	
	function __construct()
	{
		$this->min_xy = array(180.0, 90.0);
		$this->max_xy = array(-180.0, -90.0);
	}
	
	function extend($latlong)
	{
		$this->min_xy[0] = min($this->min_xy[0], $latlong[1]);
		$this->min_xy[1] = min($this->min_xy[1], $latlong[0]);

		$this->max_xy[0] = max($this->max_xy[0], $latlong[1]);
		$this->max_xy[1] = max($this->max_xy[1], $latlong[0]);
	}
	
	function toArray()
	{
		$a = array(
			$this->min_xy[0],
			$this->min_xy[1],
			$this->max_xy[0],
			$this->max_xy[1]
		);
		
		return $a;
	}
	
}
		

//-------------------------------------------------------------------------------------------------
class GeoJsonPort extends Port
{
	var $bounds = null;


	//----------------------------------------------------------------------------------------------
	function Circle($pt, $r, $action = '')
	{
	}

	//----------------------------------------------------------------------------------------------
	function DrawCircleArc($p0, $p1, $radius, $large_arc_flag = false)
	{
	}

		
	//----------------------------------------------------------------------------------------------
	function DrawLine($p0, $p1, $style = '{ "weight":2, "color":"yellow","opacity":1}')
	{
		$g = new GoogleMapsAPIProjection();	
	
		// 1. Convert pixels to lat/long		
		$pixels =  array($p0['x'], $p0['y']);		
		$long_lat0 = $g->FromPixelToCoordinates($pixels);
					
		$pixels =  array($p1['x'], $p1['y']);		
		$long_lat1 = $g->FromPixelToCoordinates($pixels);	
		
		
		$feature = new stdclass;
		$feature->type = "Feature";
		$feature->geometry = new stdclass;
		$feature->geometry->type = "LineString";
		$feature->geometry->coordinates = array();
		$feature->geometry->coordinates[] =array($long_lat0[0], $long_lat0[1]);
		$feature->geometry->coordinates[] =array($long_lat1[0], $long_lat1[1]);
		$feature->properties = new stdclass;
		
		$feature->properties->style = json_decode($style);
		
		$this->geojson->features[] = $feature;
		
		$this->bounds->extend(array($long_lat0[1], $long_lat0[0]));
		$this->bounds->extend(array($long_lat1[1], $long_lat1[0]));
	}
	
	
	//----------------------------------------------------------------------------------------------
	function DrawText ($pt, $text, $action = '')
	{
		$g = new GoogleMapsAPIProjection();	
	
		// 1. Convert pixels to lat/long		
		$pixels =  array($pt['x'], $pt['y']);		
		$long_lat = $g->FromPixelToCoordinates($pixels);
							
		$feature = new stdclass;
		$feature->type = "Feature";
		$feature->geometry = new stdclass;
		$feature->geometry->type = "Point";
		$feature->geometry->coordinates = array($long_lat[0], $long_lat[1]);
		$feature->properties = new stdclass;
		
		$feature->properties->name = $text;
		$feature->properties->popupContent = $text;
		
		$this->geojson->features[] = $feature;
		
		$this->bounds->extend(array($long_lat[1], $long_lat[0]));
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawRotatedText ($pt, $text, $action = '', $align = 'left', $angle = 0)
	{
	}
	
	
	//----------------------------------------------------------------------------------------------
	function StartPicture($centre = false)
	{
		$this->bounds = new BoundingBox();		
	
		$this->geojson = new stdclass;
		$this->geojson->type = "FeatureCollection";
		$this->geojson->bbox = array();
		$this->geojson->features = array();
    }
	
	//----------------------------------------------------------------------------------------------
	function EndPicture ()
	{
		//print_r($this->bounds);exit();
		$this->geojson->bbox = $this->bounds->toArray();
		//$this->output = json_encode($this->geojson, JSON_PRETTY_PRINT);
		$this->output = json_encode($this->geojson, JSON_PRETTY_PRINT);
	}
		
	
}

// test
if (0)
{
	$g = new GeoJsonPort(0,0,0,10);
	
	// Coordinates in pixels
	$pt1 = array('x'=> 128, 'y' => 128);
	$pt2 = array('x'=> 128, 'y' => 130);
	
	
	$g->DrawLine($pt1, $pt2);
	
	$js = $g->EndPicture();
	
	echo $js;
}

?>