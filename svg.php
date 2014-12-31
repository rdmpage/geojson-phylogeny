<?php

require_once(dirname(__FILE__) . '/port.php');

//-------------------------------------------------------------------------------------------------
class SVGPort extends Port
{
	var $document = null;
	var $node_stack = array();
	
	

	//----------------------------------------------------------------------------------------------
	function Circle($pt, $r, $action = '')
	{
		$this->output .= '<circle ' 
				. 'cx="' .$pt['x'] . '" cy="' . $pt['y'] . '" r="' . $r . '"';
		if ($action != '')
		{
			$this->output .= ' ' . $action;
		}
		$this->output .= ' />' . "\n";
				
		
	}

	//----------------------------------------------------------------------------------------------
	function DrawCircleArc($p0, $p1, $radius, $large_arc_flag = false)
	{
		$path = $this->document->createElement('path');
		
		$path->setAttribute('vector-effect', 'non-scaling-stroke');		
		
		$path_string = 'M ' 
			. $p0['x'] . ' ' . $p0['y'] // start x,y
			. ' A ' . $radius . ' ' . $radius  //
			. ' 0 ';

		if ($large_arc_flag)
		{
			$path_string .= ' 1 ';		
		}
		else
		{
			$path_string .= ' 0 ';
		}
			
		$path_string .=
			' 1 '
			. $p1['x'] . ' ' . $p1['y']; // end x,y
		
		
		$path->setAttribute('d', $path_string);		
		$n = count($this->node_stack);
		$this->node_stack[$n-1]->appendChild($path);
	
	
		/*
		$this->output .= '<path d="M ' 
			. $p0['x'] . ' ' . $p0['y'] // start x,y
			. ' A ' . $radius . ' ' . $radius  //
			. ' 0 ';
			
		if ($large_arc_flag)
		{
			$this->output .= ' 1 ';		
		}
		else
		{
			$this->output .= ' 0 ';
		}
			
		$this->output .=
			' 1 '
			. $p1['x'] . ' ' . $p1['y'] // end x,y
			. '" />' . "\n";
		*/
	}

		
	//----------------------------------------------------------------------------------------------
	function DrawLine($p0, $p1)
	{
		$path = $this->document->createElement('path');
		
		$path->setAttribute('vector-effect', 'non-scaling-stroke');
		
		$path->setAttribute('d', 
			'M ' . $p0['x'] . ' ' . $p0['y'] . ' ' . $p1['x'] . ' ' . $p1['y']);
		
		$n = count($this->node_stack);
		$this->node_stack[$n-1]->appendChild($path);
		
		/*
		$this->node_stack[] = $g;
	
		$this->output .= '<path d="M ' 
				. $p0['x'] . ' ' . $p0['y'] . ' ' . $p1['x'] . ' ' . $p1['y'] . '" />' . "\n";
		*/
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawText ($pt, $text, $action = '')
	{
		$text_node = $this->document->createElement('text');
		$text_node->setAttribute('x', $pt['x']);
		$text_node->setAttribute('y', $pt['y']);
	
		switch ($align)
		{
			case 'left':
				$text_node->setAttribute('text-anchor', 'start');
				break;
			case 'centre':
				$text_node->setAttribute('text-anchor', 'middle');
				break;
			case 'right':
				$text_node->setAttribute('text-anchor', 'end');
				break;
			default:
				$text_node->setAttribute('text-anchor', 'start');
				break;
		}
	
		$text_node->appendChild($this->document->createTextNode($text));
		
		$n = count($this->node_stack);
		$this->node_stack[$n-1]->appendChild($text_node);
	
		/*
		$this->output .= '<text x="' . $pt['x'] . '" y="' . $pt['y'] . '"';
		if ($action != '')
		{
			$this->output .= ' ' . $action;
		}
		
		switch ($align)
		{
			case 'left':
				$this->output .= ' text-anchor="start"';
				break;
			case 'centre':
				$this->output .= ' text-anchor="middle"';
				break;
			case 'right':
				$this->output .= ' text-anchor="end"';
				break;
			default:
				$this->output .= ' text-anchor="start"';
				break;
		}
		
		$this->output .= '>' . htmlentities($text) . '</text>' . "\n";
		*/
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawRotatedText ($pt, $text, $action = '', $align = 'left', $angle = 0)
	{
		$text_node = $this->document->createElement('text');
		$text_node->setAttribute('x', $pt['x']);
		$text_node->setAttribute('y', $pt['y']);
		
		switch ($align)
		{
			case 'left':
				$text_node->setAttribute('text-anchor', 'start');
				break;
			case 'centre':
				$text_node->setAttribute('text-anchor', 'middle');
				break;
			case 'right':
				$text_node->setAttribute('text-anchor', 'end');
				break;
			default:
				$text_node->setAttribute('text-anchor', 'start');
				break;
		}
		
		if ($angle != 0)
		{
			$text_node->setAttribute('transform', 'rotate(' . $angle . ' ' . $pt['x'] . ' ' . $pt['y'] . ')');
		}
		
		$text_node->appendChild($this->document->createTextNode($text));
		
		$n = count($this->node_stack);
		$this->node_stack[$n-1]->appendChild($text_node);
	
		/*	
		$this->output .= '<text x="' . $pt['x'] . '" y="' . $pt['y'] . '"';
		if ($action != '')
		{
			$this->output .= ' ' . $action;
		}
		
		switch ($align)
		{
			case 'left':
				$this->output .= ' text-anchor="start"';
				break;
			case 'centre':
				$this->output .= ' text-anchor="middle"';
				break;
			case 'right':
				$this->output .= ' text-anchor="end"';
				break;
			default:
				$this->output .= ' text-anchor="start"';
				break;
		}
		
		if ($angle != 0)
		{
			$this->output .= ' transform="rotate(' . $angle . ' ' . $pt['x'] . ' ' . $pt['y'] . ')"';
		}
		
		$this->output .= '>' . htmlentities($text) . '</text>' . "\n";
		*/
	}
	
	
	//----------------------------------------------------------------------------------------------
	function StartPicture($centre = false)
	{
		$this->document = new DomDocument('1.0', 'UTF-8');
		$svg = $this->document->createElement('svg');
		$svg->setAttribute('xmlns', 		'http://www.w3.org/2000/svg');
		$svg->setAttribute('xmlns:xlink', 	'http://www.w3.org/1999/xlink');
		$svg->setAttribute('width', 		$this->width . 'px');
		$svg->setAttribute('height', 		$this->height . 'px');
		$svg = $this->document->appendChild($svg);

		$style = $this->document->createElement('style');
		$style->setAttribute('type', 	'text/css');
		$style->appendChild($this->document->createCDATASection(
		'path {
		stroke: #000;
		stroke-width:1;
		stroke-linecap:square;
		fill: none;
	}
	text {
		alignment-baseline:middle;
		font-family:sans-serif;
		font-size: ' . $this->font_size . 'px;
	}
	text:hover {
		font-weight:bold;
		}
	circle {
		stroke: black;
		fill:white;
		opacity:0.2;
		}
	circle:hover {opacity:1.0; }'
		));
		
		$style = $svg->appendChild($style);
		
		$g = $this->document->createElement('g');
		$g->setAttribute('id', 'viewport');
		
		if ($centre)
		{
			$g->setAttribute('transform', 'translate(' . $this->width/2.0 . ' ' . $this->height/2.0 . ')');
		}
		$g = $svg->appendChild($g);
		
		$this->node_stack[] = $g;
		
	/*
	if ($centre)
	{
		$this->output .= '<g transform="translate(' . $this->width/2.0 . ' ' . $this->height/2.0 . ')">' . "\n";
	}
	else
	{
		$this->output .= '<g>' . "\n";
    }	
    */
    }
	
	//----------------------------------------------------------------------------------------------
	function EndPicture ()
	{
		//$this->output .= '</g>';
		//$this->output .= '</svg>';
	}

	//----------------------------------------------------------------------------------------------
	function OpenLink($link)
	{
		//$this->output .= '<a xlink:href="' . $link . '">';
	}	
	//----------------------------------------------------------------------------------------------
	function CloseLink()
	{
		//$this->output .= '</a>';
	}	
	
	//----------------------------------------------------------------------------------------------
	function StartGroup($group_name, $visible=true)
	{
		$group = $this->document->createElement('g');
		$group->setAttribute('id', $group_name);
		if ($visible)
		{
			$group->setAttribute('display', 'inline');
		}
		else
		{
			$group->setAttribute('display', 'none');
		}
		
		$n = count($this->node_stack);
		$this->node_stack[$n-1]->appendChild($group);
		$this->node_stack[] = $group;
		
	
		/*
		$this->output .= '<g id="' . $group_name . '"';
		if ($visible)
		{
//			$this->output .= ' style="display:inline;"';
			$this->output .= ' display="inline"';
		}
		else
		{
			//$this->output .= ' style="display:none;"';
			$this->output .= ' display="none"';
		}
		$this->output .= '>' . "\n";
		*/
	}	
	
	//----------------------------------------------------------------------------------------------
	function EndGroup()
	{
		array_pop($this->node_stack);
		
		//$this->output .= '</g>' . "\n";
	}	
	
	//----------------------------------------------------------------------------------------------
	function GetOutput()
	{
		//$this->EndPicture();
		return $this->document->saveXML();
	}
	
		
	
}

?>