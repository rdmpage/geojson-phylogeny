<?php

/**
 * @file node.php
 *
 */

//--------------------------------------------------------------------------------------------------
/**
 * @brief Node in a tree
 *
 * Node has pointers to child, sibling, and ancestral node, these pointers are
 * NULL if corresponding node doesn't exist. Has label as a field, all other values
 * are stored in an key-value array of attributes.
 */
class Node
{
	var $ancestor;
	var $child;
	var $sibling;
	var $label;
	var $id;
	var $attributes = array();
	var $cluster = array();
	var $colour = array();
	
	//----------------------------------------------------------------------------------------------
	function __construct($label = '')
	{
		$this->ancestor = NULL;
		$this->child = NULL;
		$this->sibling = NULL;
		$this->label = $label;
		$this->cluster = array();
		$this->colour = array();
	}
		
	//----------------------------------------------------------------------------------------------
	function IsLeaf()
	{
		return ($this->child == NULL);
	}
	
	//----------------------------------------------------------------------------------------------
	function AddColour($colour_set)
	{
		$this->colour = array_merge($this->colour, $colour_set); 
		$this->colour = array_unique($this->colour);
	}	
	
	//----------------------------------------------------------------------------------------------
	function AddWeight($w)
	{
		$w0 = $this->GetAttribute('weight');
		$this->SetAttribute('weight', $w0 + $w);
	}
	
	//----------------------------------------------------------------------------------------------
	function Dump()
	{
		echo "---Dump Node---\n";
		echo "   Label: " . $this->label . "\n";
		echo "      Id: " . $this->id . "\n";
		echo "   Child: ";
		if ($this->child == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->child->label . "\n";
		}
		echo " Sibling: ";
		if ($this->sibling == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->sibling->label . "\n";
		}
		echo "Ancestor: ";
		if ($this->ancestor == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->ancestor->label . "\n";
		}
		echo "Attributes:\n";
		print_r($this->attributes);
		echo "Cluster:\n";
		print_r($this->cluster);
	}
	
	//----------------------------------------------------------------------------------------------
	function GetAncestor() { return $this->ancestor; }	
	
	//----------------------------------------------------------------------------------------------
	function GetAttribute($key) 
	{
		if (isset($this->attributes[$key]))
		{			
			return $this->attributes[$key];
		}
		else
		{
			return null;
		}
	}		

	//----------------------------------------------------------------------------------------------
	function GetChild() { return $this->child; }	
	
	//----------------------------------------------------------------------------------------------
	function GetColour() { return $this->colour; }	

	//----------------------------------------------------------------------------------------------
	function GetId() { return $this->id; }	

	//----------------------------------------------------------------------------------------------
	function GetLabel() { return $this->label; }	
	
	//----------------------------------------------------------------------------------------------
	// If node is sibling get node immediately preceding it ("to the left")
	function GetLeftSibling()
	{
		$q = $this->ancestor->child;
		while ($q->sibling != $this)
		{
			$q = $q->sibling;
		}
		return $q;
	}
	
	//----------------------------------------------------------------------------------------------
	function GetRightMostSibling()
	{
		$p = $this;
		
		while ($p->sibling)
		{
			$p = $p->sibling;
		}
		return $p;
	}


	//----------------------------------------------------------------------------------------------
	function GetSibling() { return $this->sibling; }	
	
	//----------------------------------------------------------------------------------------------
	function IsChild()
	{
		$is_child = false;
		$q = $this->ancestor;
		if ($q)
		{
			$is_child = ($q->child == $this);
		}
		return $is_child;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetAncestor($p)
	{
		$this->ancestor = $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetChild($p)
	{
		$this->child = $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetColour($colour)
	{
		$this->colour = array($colour);
	}	
	
	//----------------------------------------------------------------------------------------------
	function SetId($id)
	{
		$this->id = $id;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetLabel($label)
	{
		$this->label = $label;
	}
	

	//----------------------------------------------------------------------------------------------
	function SetSibling($p)
	{
		$this->sibling = $p;
	}
	
	//----------------------------------------------------------------------------------------------
	// Children of node (as array)
	function GetChildren()
	{
		$children = array();
		$p = $this->child;
		if ($p)
		{
			array_push($children, $p);
			$p = $p->sibling;
			while ($p)
			{
				array_push($children, $p);
				$p = $p->sibling;
			}
		}
		return $children;
	}
	
	
}

?>