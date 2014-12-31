<?php

// treelib-php
require_once('svg.php');
require_once('node_iterator.php');
require_once('tree.php');
require_once('tree_drawer.php');
require_once('nexus.php');



// GenGIS
require_once('crossing.php');
require_once('perm.php');

// GeoJSON
require_once('geojson_port.php');

// Get data
require_once('read.php');


//--------------------------------------------------------------------------------------------------
function cross_product($p1, $p2, $p3)
{
	return ($p2[0] - $p1[0]) * ($p3[1] - $p1[1]) - ($p3[0] - $p1[0]) * ($p2[1] - $p1[1]);
}

//--------------------------------------------------------------------------------------------------
// From http://en.wikipedia.org/wiki/Graham_scan
function convex_hull($points)
{
	// Find pivot point (has lowest y-value)
	$minX = 180.0;
	$minY = 90;
	$pivot = 0;

	$n = count($points);

	for ($i=0;  $i < $n; $i++)
	{	
		if ($points[$i][1] <= $minY)
		{
			if ($points[$i][1] < $minY)
			{
				$pivot = $i;
				$minY = $points[$i][1];
				$minX = $points[$i][0];
			}
			else
			{
				if ($points[$i][0] < $minX)
				{
					$pivot = $i;
					$minX = $points[$i][0];
				}
			}
		}
	}

	$angle = array();
	$distance = array();

	// Compute tangents
	for ($i=0;  $i < $n; $i++)
	{	
		if ($i != $pivot)
		{
			$o = $points[$i][1] - $points[$pivot][1];
			$a = $points[$i][0] - $points[$pivot][0];		
			$h = sqrt($a*$a + $o*$o); 
		
			array_push($angle, rad2deg(atan2($o, $a)));
			array_push($distance, $h);
		}
		else
		{
			array_push($angle, 0.0);
			array_push($distance, 0.0);
		}
	}

	// Sort array of points by angle, then distance
	array_multisort($angle, SORT_ASC, $distance, SORT_DESC, $points);

	// Fnd hull
	$stack = array();
	array_push($stack, $points[0]);
	array_push($stack, $points[1]);

	for ($i = 2; $i < $n; $i++)
	{
		$stack_count = count($stack);
		$cp = cross_product($stack[$stack_count-2], $stack[$stack_count-1], $points[$i]);
		while ($cp <= 0 && $stack_count >= 2)
		{
			array_pop($stack);
			$stack_count = count($stack);
			$cp = cross_product($stack[$stack_count-2], $stack[$stack_count-1], $points[$i]);
		}
		array_push($stack, $points[$i]);
	}

	return $stack;
}


//--------------------------------------------------------------------------------------------------
function get_taxa_from_tree($treeblock)
{
	$taxa = array();
	
	if (isset($treeblock->translations->translate))
	{
		foreach ($treeblock->translations->translate as $k => $v)
		{
			$taxa[] = $v;
		}
	}
	
	return $taxa;
}

//--------------------------------------------------------------------------------------------------
function get_subtree_leaf_order($subtree)
{
	$sequence = array();
	
	$ni = new NodeIterator ($subtree);
	$q = $ni->Begin();
	while ($q != NULL)
	{	
		if ($q->IsLeaf ())
		{
			$sequence[] = $q->GetLabel();
		}
		$q = $ni->Next();
	}
	
	return $sequence;
}

//--------------------------------------------------------------------------------------------------
function get_subtree_geo_order($leaf_sequence, $geo_order)
{
	// Convert absolute geo order to [0,m] where m is number of distinct localities 
	// occupied by this subtree
	$relative_order = array();
	foreach ($leaf_sequence as $k => $v)
	{
		if (isset($geo_order[$v]))
		{
			$relative_order[] = $geo_order[$v];
		}
	}
	
	sort($relative_order);
	$relative_order = array_flip($relative_order);

	return $relative_order;
}

//--------------------------------------------------------------------------------------------------
function layer_crossings($leaf_sequence, $geo_order)
{
	$relative_geo_order = get_subtree_geo_order($leaf_sequence, $geo_order);
			
	// Generate bipartite layer
	$layer = array();
	foreach ($leaf_sequence as $k => $v)
	{
		$layer[] = array($k, $relative_geo_order[$geo_order[$v]]);
	}
	
	// Count number of crossings
	return count_crossings($layer);
}


//--------------------------------------------------------------------------------------------------
class GeoLayout
{
	var $occurrences = array();
	var $bounds;
	
	var $tree;
	
	var $latitude;
	var $longitude;
	
	var $leaf_list = array();

	var $occurrence_list = array();
	var $occurrence_label_to_order = array();
	
	var $edges = array();
	
	var $bins = array();
	
	var $taxa = array();
	var $taxon_colours = array();
	var $occurrence_colours = array();
	
	var $port = null;

	//----------------------------------------------------------------------------------------------
	function __construct($t)
	{
		$this->bounds = new BoundingBox();	
		$this->tree = $t;	
		
		$latitude = array();
		$longitude = array();
		
		$this->port = new GeoJsonPort('', $rect->width, $rect->height, 10, false);	
	}

	//----------------------------------------------------------------------------------------------
	function add_occurrence($label, $latlong)
	{
		$this->occurrences[$label] = $latlong;
		
		$this->latitude[] 	= $latlong[0];
		$this->longitude[] 	= $latlong[1];
		
		$this->bounds->extend($latlong);
		
		// Initially each occurrence is in its own "bin"
		$this->bins[$label] = $label;
	}
	
	//----------------------------------------------------------------------------------------------
	function add_bin($label, $bin_label)
	{
		$this->bins[$label] = $bin_label;
	}
	
	//----------------------------------------------------------------------------------------------
	function sort_occurrences()
	{
		array_multisort($this->latitude, SORT_DESC, SORT_NUMERIC, $this->occurrences); 
		
		// Clean up
		unset($this->latitude);
		unset($this->longitude);	
	}
	
	//----------------------------------------------------------------------------------------------
	function make_bipartite_graph()
	{
		$this->sort_occurrences();
		
		// on other side vertices are the occurrences 
		$i = 0;
		foreach ($this->occurrences as $label => $latlong)
		{
			$this->occurrence_list[] = $label;
			$this->occurrence_label_to_order[$label] = $i++;
		}
		
		$this->reorder_tree();
	
		// on one side the vertices corresponding to leaves in the tree,
		// in the order they appear in the tree
		$this->leaf_list = get_subtree_leaf_order($this->tree->GetRoot());
		
		foreach ($this->leaf_list as $leaf_index => $label)
		{
			if (isset($this->occurrence_label_to_order[$label]))
			{
				$this->edges[] = array($leaf_index, $this->occurrence_label_to_order[$label]);
			}
		}		
	}
	
	//----------------------------------------------------------------------------------------------
	// Bounding box for where we will draw the tree
	function get_tree_rect()
	{
		$rect = new stdclass;
		
		$g = new GoogleMapsAPIProjection(0);
		
		$top_longlat = array($this->bounds->min_xy[0], $this->bounds->max_xy[1]);
		$bottom_longlat = array($this->bounds->min_xy[0], $this->bounds->min_xy[1]);
		
		$pixel_top = $g->FromCoordinatesToPixel($top_longlat);
		$pixel_bottom = $g->FromCoordinatesToPixel($bottom_longlat);
		
		$span = $pixel_bottom[1] - $pixel_top[1];
		
		$rect->height = $span; 			// vertical span of occurrences
		$rect->width = $rect->height;	// make tree fit in square box
		
		// origin of tree (top left in pixel coordinates)
		$rect->x = $pixel_top[0] - $span;
		$rect->y = $pixel_top[1];
		
		return $rect;	
	}
	
	//----------------------------------------------------------------------------------------------
	function draw_tree()
	{
		$rect = $this->get_tree_rect();
		
		$this->tree->BuildWeights($this->tree->GetRoot());
		
		// Drawing properties
		$attr = array();
		$attr['inset']			= 0;
		$attr['width'] 			= $rect->width/2.0;
		$attr['height'] 		= $rect->height;
		
		$attr['font_height'] 	= 10;
		$attr['line_width'] 	= 1;
	
		// Don't draw labels (we do this afterwards)
		$attr['draw_leaf_labels'] = false;
		$attr['draw_internal_labels'] = false;
		
		$attr['draw_scale_bar'] = false;
	
		$td = NULL;
	
		if ($this->tree->HasBranchLengths())
		{
			$td = new PhylogramTreeDrawer($this->tree, $attr);
		}
		else
		{
			$td = new RectangleTreeDrawer($this->tree, $attr);
		}
		
		$td->CalcCoordinates();	
	
		// offset everything (doesn't do scale bar yet)
		$n = new NodeIterator ($this->tree->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{					
			$pt = $q->GetAttribute('xy');
			$pt['x'] += $rect->x;
			$pt['y'] += $rect->y;
			$q->SetAttribute('xy', $pt);
			
			$q = $n->Next();
		}
		
		
		// colour tree
		{
			$n = new NodeIterator ($this->tree->getRoot());
			
			$q = $n->Begin();
			while ($q != NULL)
			{					
				if ($q->IsLeaf())
				{
					$colour = $this->occurrence_colours[$q->GetLabel()];
					$q->SetColour($colour);					
				}
				
				$anc = $q->GetAncestor();
				if ($anc)
				{
					$anc->AddColour($q->GetColour());
				}
				
				$q = $n->Next();
			}
		}
		
		
		
		$td->Draw($this->port);
	}
	
	//----------------------------------------------------------------------------------------------
	function reorder_tree()
	{
		$ni = new NodeIterator ($this->tree->GetRoot());
		$q = $ni->Begin();
		while ($q != NULL)
		{	
			if (!$q->IsLeaf ())
			{
				// Get children			
				$child_nodes = array();
				$child_nodes[] = $q->GetChild();
				$r = $q->child->sibling;
				while ($r)
				{
					$child_nodes[] = $r;
					$r = $r->sibling; 
				}
			
				// what is degree of node?
				$degree = count($child_nodes);
				
				if ($degree > 2)
				{
					$children = array();
					foreach ($child_nodes as $c)
					{
						$children[] = get_subtree_leaf_order($c);
					}
					
					// try different arrangements
					$p = range(0, $degree-1);
					
					$best_score = 1000;
					$best_perm = $p;
	
					while ($p)
					{
						$leaf_sequence = array();
						foreach ($p as $p_i)
						{
							$leaf_sequence = array_merge($leaf_sequence, $children[$p_i]);
						}
					
						$score = layer_crossings($leaf_sequence, $this->occurrence_label_to_order);
						
						if ($score < $best_score)
						{
							$best_score = $score;
							$best_perm = $p;
						}
	
						$p = pc_next_permutation($p);
						
					}
					
					// update tree				
					for ($i = 0; $i < $degree; $i++)
					{
						if ($i == 0)
						{
							$q->SetChild($child_nodes[$best_perm[$i]]);
						}
						else
						{
							$child_nodes[$best_perm[$i]]->SetSibling(null);
							$child_nodes[$best_perm[$i-1]]->SetSibling($child_nodes[$best_perm[$i]]);
						}	
					}	
				}
				else
				{
					// simple swap test
					$children = array();
					
					$children[] = get_subtree_leaf_order($child_nodes[0]);
					$children[] = get_subtree_leaf_order($child_nodes[1]);
	
					// Complete list of leafs using left + right descendant	[0,n] where n is number of leaves		
					$leaf_sequence = array_merge($children[0], $children[1]);
					
					$score1 = layer_crossings($leaf_sequence, $this->occurrence_label_to_order);
					
					$leaf_sequence = array_merge($children[1], $children[0]);
								
					$score2 = layer_crossings($leaf_sequence, $this->occurrence_label_to_order);
										
					if ($score2 < $score1)
					{
						// swap children				
						$left = $q->GetChild();
						$right = $q->GetChild()->GetSibling();
						
						$q->SetChild($right);
						$left->SetSibling($right->GetSibling());
						$right->SetSibling($left);
					}
				}			
	
	
			}
			$q = $ni->Next();
		}	
	}
	
	//----------------------------------------------------------------------------------------------
	function get_taxa()
	{
		$this->taxa = array();
		
		foreach ($this->bins as $occurrence_label => $bin)
		{
			if (!isset($this->taxa[$bin]))
			{
				$this->taxa[$bin] = array();
			}
			$this->taxa[$bin][] = $occurrence_label;		
		}
	}

	//----------------------------------------------------------------------------------------------
	function colour_taxa()
	{
		$this->taxon_colours = array();
		$this->occurrence_colours = array();
		
		// https://github.com/mbostock/d3/wiki/Ordinal-Scales
		$category20c = array('#3182bd','#6baed6','#9ecae1','#c6dbef','#e6550d','#fd8d3c','#fdae6b','#fdd0a2','#31a354','#74c476','#a1d99b','#c7e9c0','#756bb1','#9e9ac8','#bcbddc','#dadaeb','#636363','#969696','#bdbdbd','#d9d9d9');
		$category10 = array('#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f','#bcbd22','#17becf');
		
		$c = array('yellow', 'orange', 'purple', 'red', 'blue');
		
		$colour_list = $category10;

		$count = 0;
		foreach ($this->taxa as $taxon_name => $taxon)
		{
			// pick a colour
			$colour = $colour_list[$count++ % count($colour_list)];
			
			$this->taxon_colours[$taxon_name] = $colour;
			
			foreach ($taxon as $occurrence_label)
			{
				$this->occurrence_colours[$occurrence_label] = $colour;
			}
		}
	}
	
	
	//----------------------------------------------------------------------------------------------
	function draw_taxa()
	{
		// do we have any bins with > 1 member?
		// if so, draw polygon
				
		foreach ($this->taxa as $taxon_name => $taxon)
		{
			if (count($taxon) > 1)
			{
				$pts = array();
				foreach ($taxon as $occurrence_label)
				{
					if (isset($this->occurrences[$occurrence_label]))
					{
						$pt = array(
							$this->occurrences[$occurrence_label][1],
							$this->occurrences[$occurrence_label][0]
							);
							
						$pts[] = $pt;
					}
				}
				
				if (count($pts) > 1)
				{
					
					$s = convex_hull($pts);
					
					$s[] = $s[0];
				
					$feature = new stdclass;
					$feature->type = 'Feature';
		
					$feature->properties = new stdclass;
					$feature->properties->name = $taxon_name;
				
					$feature->geometry = new stdclass;
					$feature->geometry->type = 'Polygon';
					$feature->geometry->coordinates = array();
					$feature->geometry->coordinates[] = $s;
					
					$feature->properties->style = new stdclass;
					$feature->properties->style->color = $this->taxon_colours[$taxon_name];
					$feature->properties->style->weight = 1;
					$feature->properties->style->fillOpacity = 0.4;
					
				
					$this->port->geojson->features[] = $feature;
				}
			}
		}
	}
	
	
	//----------------------------------------------------------------------------------------------
	function get_geojson()
	{
		$this->get_taxa();
		$this->colour_taxa();
	
		// tree
		$this->draw_tree();
		
		$g = new GoogleMapsAPIProjection(0);
		
		$top_longlat = array($this->bounds->min_xy[0], $this->bounds->max_xy[1]);
		$bottom_longlat = array($this->bounds->min_xy[0], $this->bounds->min_xy[1]);
		
		$pixel_top = $g->FromCoordinatesToPixel($top_longlat);
		$pixel_bottom = $g->FromCoordinatesToPixel($bottom_longlat);
		
		$span = $pixel_bottom[1] - $pixel_top[1];
		
		$n = $this->tree->GetNumLeaves();
		$n2 = count($this->edges);
		
		$leaf_gap = $span / ($n - 1); // vertical gap between leaves in tree
		$occurrence_gap = $span / ($n2 - 1); // vertical gap between occurrences 
		
		// Bipartite graph
		foreach ($this->edges as $edge)
		{
			// Mapping between leaves and occurrences
			$left_pixel = array(
				'x' => $pixel_top[0]  - (($n-1)/2) * $leaf_gap,
				'y' => $pixel_top[1] + ($edge[0] * $leaf_gap)
				);
									
			$right_pixel = array(
//				'x' => $pixel_top[0],
				'x' => $left_pixel['x'] + 4 * $leaf_gap,
				'y' => $pixel_top[1] + ($edge[1] * $occurrence_gap)
				);
				
				
			$occurence_label = $this->occurrence_list[$edge[1]];
				
			$style = '{"weight":2,"color":"' . $this->occurrence_colours[$occurence_label] . '","opacity":1}';
			
			$this->port->DrawLine($left_pixel, $right_pixel, $style);
			
			// Line from edge to occurrence on map
			
			$occurrence_longlat = array(
				$this->occurrences[$occurence_label][1],
				$this->occurrences[$occurence_label][0] 
				);
			$occurrence_coordinates = $g->FromCoordinatesToPixel($occurrence_longlat);
			$occurrence_pixel = array(
				'x' => $occurrence_coordinates[0],
				'y' => $occurrence_coordinates[1]
				);
			
			$this->port->DrawLine($right_pixel, $occurrence_pixel, $style);
			
			// occurrence itself
			if (1)
			{
				$this->port->DrawText($occurrence_pixel, $occurence_label);
			}
		}
		
		// Polygons for taxa
		$this->draw_taxa();
		
		return $this->port->GetOutput();	
	}
	
	
	
}

//--------------------------------------------------------------------------------------------------
function create_geojson($filename)
{
	$nexus = file_get_contents($filename);
	
	
	$data = read_nexus($nexus);
	
	// get tree
	
	$taxa = get_taxa_from_tree($data->treeblock);
	$newick = $data->treeblock->trees[0]->newick;
	
	$t = new Tree();
	$t->Parse($newick);
	
	$ni = new NodeIterator ($t->getRoot());
			
	$q = $ni->Begin();
	while ($q != NULL)
	{	
		if ($q->IsLeaf ())
		{
			if (isset($data->treeblock->translations->translate))
			{
				$q->SetLabel($data->treeblock->translations->translate[$q->GetLabel()]);
			}
		}
		$q = $ni->Next();
	}
	
	$g = new GeoLayout($t);
	
	
	// get coordinates	
	foreach ($data->characters->matrix as $taxa => $pair)
	{
		$latlong = array();
		
		if (isset($pair['latitude']))
		{
			$latlong[] = $pair['latitude'];
		}
		if (isset($pair['longitude']))
		{
			$latlong[] = $pair['longitude'];
		}
			
		$g->add_occurrence($taxa, $latlong);
		
	}
	
	// taxa
	if (isset($data->notes))
	{
		$n = count($data->notes->alttaxnames[0]->names);
		
		for ($i = 0; $i < $n; $i++)
		{		
			$g->add_bin($data->treeblock->translations->translate[$i+1], $data->notes->alttaxnames[0]->names[$i]);
		}
	}
	
	$g->make_bipartite_graph();
	
	
	$json = $g->get_geojson();
	
	return $json;
}

?>