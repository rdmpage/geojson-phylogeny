<?php

require_once(dirname(__FILE__) . '/tree.php');
require_once(dirname(__FILE__) . '/port.php');

//-------------------------------------------------------------------------------------------------
class TreeDrawer
{
	var $t;
	var $width 		= 0;
	var $height 	= 0;
	var $left 		= 0;
	var $top 		= 0;
	var $leaf_count = 0;
	var $leaf_gap	= 0;
	var $node_gap	= 0;
	var $last_y		= 0;
	var $max_depth 	= 0;
	var $last_label = 0;
	var $max_height = 0;
	
	var $draw_leaf_labels = true;
	var $draw_scale_bar = true;
	
	var $map = '';
	
	var $settings = array();
	
	var $port;
	
	
	//----------------------------------------------------------------------------------------------
	function __construct($tree, $attr)
	{
		$this->t = $tree;
		
		// Settings
		$this->settings = $attr;		
		
		// Ensure sensible defaults
		$this->SetDefaults();
			
		$this->left = $this->settings['inset'];
		$this->top = $this->settings['inset'];
		$this->width = $this->settings['width'] - 2 * $this->settings['inset'];
		$this->height = $this->settings['height'] - 2 * $this->settings['inset'];
				
		$this->last_label = -($this->settings['font_height']/2.0);

		if (isset($this->settings['draw_leaf_labels']))
		{
			$this->draw_leaf_labels = $this->settings['draw_leaf_labels'];
		}	
		if (isset($this->settings['draw_scale_bar']))
		{
			$this->draw_scale_bar = $this->settings['draw_scale_bar'];
		}	
	}
	
	//----------------------------------------------------------------------------------------------
	function SetDefaults()
	{
		if (!isset($this->settings['font_height']))
		{
			$this->settings['font_height'] = 10;
		}	
		if (!isset($this->settings['inset']))
		{
			$this->settings['inset'] = $this->settings['font_height'];
		}
		if (!isset($this->settings['width']))
		{
			$this->settings['width'] = 200;
		}
		if (!isset($this->settings['height']))
		{
			$this->settings['height'] = 400;
		}
	}
	
	
	//----------------------------------------------------------------------------------------------
	function CalcInternal($p)
	{
		// Cladogram
		$pt = array();		
		$pt['x'] = $this->left + ($this->node_gap * ($this->t->GetNumLeaves() - $p->GetAttribute('weight')));
		$pt['y'] = $this->last_y - (($p->GetAttribute('weight') - 1) * $this->leaf_gap)/2.0;

		$p->SetAttribute('xy', $pt);
	}

	//----------------------------------------------------------------------------------------------
	function CalcLeaf($p)
	{
		$pt = array();
		$pt['y'] = $this->top + $this->leaf_count * $this->leaf_gap;
		$this->last_y = $pt['y'];
		$this->leaf_count++;
		
		// cladogram
		$pt['x'] = $this->left + $this->width;
		
		$p->SetAttribute('xy', $pt);
		
		// image map
		/*$this->map .= '<area shape="rect" coords="' 
		. ($pt['x'] + 10) . ',' . ($pt['y'] - 5) . ',' . ($pt['x'] + 10 + strlen($p->GetLabel()) * 10) . ',' . ($pt['y'] + 5) 
		. '" href="http://www.ncbi.nlm.nih.gov/nuccore/' . $p->GetLabel() . '" />' . "\n";*/
		
		$this->max_height = max($this->max_height, $pt['y']);
	}
	
	//----------------------------------------------------------------------------------------------
	function CalcCoordinates()
	{
		$leaves = $this->t->GetNumLeaves();
		$this->leaf_count = 0;
   		$this->leaf_gap = $this->height / ($leaves - 1.0);
   		
   		if ($this->t->IsRooted())		
		{
			$this->node_gap = $this->width / ($leaves);
			
			$this->left += $this->node_gap; 
			$this->width -= $this->node_gap; 
		}
		else
		{
			$this->node_gap = $this->width / ($leaves - 1.0);	
		}
		
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{
			
			if ($q->IsLeaf ())
			{
				$this->CalcLeaf ($q);
			}
			else
			{
				$this->CalcInternal ($q);
			}
	
			$q = $n->Next();
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Draw($port)
	{
		$this->port = $port;
		
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{			
			if ($q->IsLeaf ())
			{
				$this->DrawLeaf ($q);
			}
			else
			{
				$this->DrawInternal ($q);
			}
	
			$q = $n->Next();
		}
		if ($this->t->IsRooted())
		{
			$this->DrawRoot ();
		}

	}
	
	//----------------------------------------------------------------------------------------------
	function DrawLeaf($p)
	{
		$anc = $p->GetAncestor();
		if ($anc)
		{
			// Slant
			$p0 = $p->GetAttribute('xy');
			$p1 = $anc->GetAttribute('xy');

			$this->port->DrawLine($p0, $p1);
 		}
 		
 		if ($this->draw_leaf_labels)
 		{
 			$this->DrawLeafLabel ($p);	
 		}
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawLeaflabel($p)
	{
		$p0 = $p->GetAttribute('xy');
		
		if ($p0['y'] - $this->last_label > $this->settings['font_height'])
		{
			$this->port->DrawText($p0, $p->Getlabel()); 
			$this->last_label  = $p0['y'];
		}
	}

	//----------------------------------------------------------------------------------------------
	function DrawLine($p0, $p1)
	{
		echo 'context.moveTo(' . $p0['x'] . ',' . $p0['y'] . ');' . "\n";
		echo 'context.lineTo(' . $p1['x'] . ',' . $p1['y'] . ');' . "\n";
		echo 'context.stroke();' . "\n";
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawInternal($p)
	{
		$p0 = $p->GetAttribute('xy');
		$anc = $p->GetAncestor();
		if ($anc)
		{
			// Slant
			$p1 = $anc->GetAttribute('xy');
			$this->port->DrawLine($p0, $p1);
		}
 		$this->DrawInternalLabel ($p);		
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawInternalLabel($p)
	{
		if ($p->GetLabel())
		{
			// to do test
			$p0 = $p->GetAttribute('xy');
		}

	}
	
	//----------------------------------------------------------------------------------------------
	function DrawRoot()
	{
		$p0 = $this->t->GetRoot()->GetAttribute('xy');
		$p1 = $p0;
		$p1['x'] -= $this->node_gap;
		//$this->DrawLine($p0, $p1);
		
		$style = '{"weight":2,"color":"' . $this->t->GetRoot()->GetColour()[0] . '", "opacity":1}';
		
		
		$this->port->DrawLine($p0, $p1, $style);
	}
	
	//----------------------------------------------------------------------------------------------
	function GetMap()
	{
		return $this->map;
	}
	
	
}

//-------------------------------------------------------------------------------------------------
class RectangleTreeDrawer extends TreeDrawer
{
	
	
	//----------------------------------------------------------------------------------------------
	function CalcInternal($p)
	{
		$pt['x'] = $this->left + $this->node_gap * ($this->max_depth - $p->GetAttribute('depth'));
    	
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$pt['y'] = $pl['y'] + ($pr['y'] - $pl['y'])/2.0;
   	
		$p->SetAttribute('xy', $pt);
	}

	
	//----------------------------------------------------------------------------------------------
	function CalcCoordinates()
	{
		// rectangle		
		foreach ($this->t->nodes as $n)
		{
			$n->SetAttribute('depth', 0);
		}
		$this->max_depth = 0;
		foreach ($this->t->nodes as $n)
		{
			if ($n->IsLeaf())
			{
				$p = $n->GetAncestor();
				$count = 1;
				while ($p)
				{
					if ($count > $p->GetAttribute('depth'))
					{
						$p->SetAttribute('depth', $count);
						$this->max_depth = max($this->max_depth, $count);
					}
					$count++;
					$p = $p->GetAncestor();
				}
			}
		}						
		$leaves = $this->t->GetNumLeaves();
		$this->leaf_count = 0;
   		$this->leaf_gap = $this->height / ($leaves - 1.0);
   		
   		if ($this->t->IsRooted())		
		{
			$this->node_gap = $this->width / ($this->max_depth + 1);
			
			$this->left += $this->node_gap; 
			$this->width -= $this->node_gap; 
		}
		else
		{
//			$this->node_gap = $this->width / ($leaves - 1.0);	
			$this->node_gap = $this->width / $this->max_depth;	
		}
		
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{
			
			if ($q->IsLeaf ())
			{
				$this->CalcLeaf ($q);
			}
			else
			{
				$this->CalcInternal ($q);
			}
	
			$q = $n->Next();
		}
	}
	
	
	//----------------------------------------------------------------------------------------------
	function DrawLeaf($p)
	{
		$anc = $p->GetAncestor();
		if ($anc)
		{

			// Rectangle
			$p0 = $p->GetAttribute('xy');
			$p1 = $anc->GetAttribute('xy');
			$p1['y'] = $p0['y'];
			
			$style = '{"weight":2,"color":"' . $p->GetColour()[0] . '", "opacity":1}';
			
			//$this->port->DrawLine($p0, $p1, $style);
			
			$p0 = $p->GetAttribute('xy');
			$p1 = $p->GetAttribute('backarc');
			
			$style = '{"weight":2,"color":"' . $p->GetColour()[0] . '", "opacity":1}';
			$this->port->DrawLine($p0, $p1, $style);
			
 		}
 		
 		if ($this->draw_leaf_labels)
 		{
 			$this->DrawLeafLabel ($p);	
 		}
	}
	
	
	//----------------------------------------------------------------------------------------------
	function DrawInternal($p)
	{
		$p0 = $p->GetAttribute('xy');
		$anc = $p->GetAncestor();
		if ($anc)
		{
			$p1 = $anc->GetAttribute('xy');
			$p1['y'] = $p0['y'];
				
			$style = '{"weight":2,"color":"' . $p->GetColour()[0] . '", "opacity":1}';
			
			//$this->port->DrawLine($p0, $p1, $style);
			
			$p0 = $p->GetAttribute('xy');
			$p1 = $p->GetAttribute('backarc');
			
		$colour = 'white';
		$colour = 'rgb(128,128,128)';
		if (count($p->GetColour()) == 1)
		{
			$colour = $p->GetColour()[0];
		}
			
			
			$style = '{"weight":2,"color":"' . $colour . '", "opacity":1}';
			$this->port->DrawLine($p0, $p1, $style);
			
		}
		
		// rectangle
		/*
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$p0['x'] = $p0['x'];
		$p0['y'] = $pl['y'];
		$p1['x'] = $p0['x'];
		$p1['y'] = $pr['y'];
		*/

		$pl = $p->GetChild()->GetAttribute('backarc');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('backarc');
		
		$colour = 'white';
		$colour = 'rgb(128,128,128)';
		if (count($p->GetColour()) == 1)
		{
			$colour = $p->GetColour()[0];
		}
		//echo join(",", $p->GetColour()) . "\n";
		
		$style = '{"weight":2,"color":"' . $colour . '", "opacity":1}';
			
		//$this->port->DrawLine($p0, $p1, $style);
		$this->port->DrawLine($pl, $pr, $style);
		
 		$this->DrawInternalLabel ($p);		
	}
	

	
}


//-------------------------------------------------------------------------------------------------
class PhylogramTreeDrawer extends RectangleTreeDrawer
{
	var $max_path_length = 0.0;
	
	
	//----------------------------------------------------------------------------------------------
	function CalcInternal($p)
	{
		$pt = array();
		$pt['x'] = $this->left + ($p->GetAttribute('path_length') / $this->max_path_length) * $this->width;
    	
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$pt['y'] = $pl['y'] + ($pr['y'] - $pl['y'])/2.0;
   	
		$p->SetAttribute('xy', $pt);
		
		// back arcs
		$child = $p->GetChild();
		while ($child)
		{
			$child_pt = $child->GetAttribute('xy');
			$child_pt['x'] = $pt['x'];
			
			$child->SetAttribute('backarc', $child_pt);
			$child =  $child->GetSibling();
		}
		
		
	}
	
	//----------------------------------------------------------------------------------------------
	function CalcLeaf($p)
	{
		$pt = array();
		$pt['y'] = $this->top + $this->leaf_count * $this->leaf_gap;
		$this->last_y = $pt['y'];
		$this->leaf_count++;
		
		// cladogram
		$pt['x'] = $this->left + ($p->GetAttribute('path_length') / $this->max_path_length) * $this->width;
		
		$p->SetAttribute('xy', $pt);
		
		$this->max_height = max($this->max_height, $pt['y']);
	}		

	
	//----------------------------------------------------------------------------------------------
	function CalcCoordinates()
	{
		$this->max_path_length = 0.0;		
		$this->t->GetRoot()->SetAttribute('path_length', $this->t->GetRoot()->GetAttribute('edge_length'));

		// Get path lengths
		$n = new PreorderIterator ($this->t->getRoot());
		$q = $n->Begin();
		while ($q != NULL)
		{			
			$d = $q->GetAttribute('edge_length');
			if ($d < 0.00001)
			{
				$d = 0.0;
			}
        	if ($q != $this->t->GetRoot())
	    		$q->SetAttribute('path_length', $q->GetAncestor()->GetAttribute('path_length') + $d);

			$this->max_path_length = max($this->max_path_length, $q->GetAttribute('path_length'));
			$q = $n->Next();
		}

		if ($this->draw_scale_bar)
		{
			$this->height -= $this->settings['font_height'];
		}

		$leaves = $this->t->GetNumLeaves();
		$this->leaf_count = 0;
   		$this->leaf_gap = $this->height / ($leaves - 1.0);

		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{
			
			if ($q->IsLeaf ())
			{
				$this->CalcLeaf ($q);
			}
			else
			{
				$this->CalcInternal ($q);
			}
	
			$q = $n->Next();
		}
		
		// Space for scale bar
		if ($this->draw_scale_bar)
		{
			$this->max_height += $this->settings['font_height'];
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Draw($port)
	{
		parent::Draw($port);
		if ($this->draw_scale_bar)
		{
			$this->DrawScaleBar($port);
		}
	}

	//----------------------------------------------------------------------------------------------
	function DrawScaleBar($port)
	{
	/*
		$pt1 = array();
		$pt2 = array();
		
		$m = log10($this->max_path_length);
		$i = floor($m);
		//     if (!mUltrametric)

		//$i -= 1;
		$bar = pow(10.0, $i);
		
		//echo $bar;
		
		$scalebar = ($bar/$this->max_path_length) * $this->width;
		
		if (0)
		{
		}
		else
		{
			// Scale bar
			$pt1['x'] = $this->left;
			$pt1['y'] = $this->top + $this->height + $this->settings['font_height'];

			$pt2['x'] = $pt1['x'] + $scalebar;
			$pt2['y'] = $pt1['y'];
			
			
			$this->port->DrawLine($pt1, $pt2);
			
			// Label
			$buf = '';
			if ($i >= 0)
			{
				$buf = sprintf ("%d", floor($bar));
			}
			else
			{
				$j = abs ($i);
				$buf = sprintf ("%." . $j . "f", $bar);
			}
			// Offset value from scale bar
			$pt2['x'] += $this->settings['font_height']/3;			
			$this->port->DrawText($pt2, $buf);
			
  		}
  		*/
	}
	
	
}	

?>