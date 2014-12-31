<?php

require_once(dirname(__FILE__) . '/node.php');

/**
 *
 * @file node_iterator.php
 *
 */

//--------------------------------------------------------------------------------------------------
/**
 * @brief
 *
 * Iterator that visits nodes in a tree in post order. Uses a stack to keep
 * track of place in tree. 
 *
 */
class NodeIterator
{
	var $root;
	var $cur;
	var $stack;
	
	//----------------------------------------------------------------------------------------------
	/**
	 * @brief Takes the root of the tree as a parameter.
	 *
     * @param r the root of the tree
	 */
	function __construct($r)
	{
		$this->root = $r;
		$this->stack = array();
		$this->cur = null;
	}
	
	//----------------------------------------------------------------------------------------------
	/**
	 * @brief Initialise iterator and returns the first node.
	 *
	 * Initialises the 
	 * @return The first node of the tree
	 */
	function Begin()
	{
		$this->cur = $this->root;
		while ($this->cur->GetChild())
		{
			array_push($this->stack, $this->cur);			
			$this->cur = $this->cur->GetChild();
		}
		return $this->cur;	
	}
	
	//----------------------------------------------------------------------------------------------
 	/**
	 * @brief Move to the next node in the tree.
	 *
	 * @return The next node in the tree, or NULL if all nodes have been visited.
	 */
	function Next()
	{
		if (count($this->stack) == 0)
		{
			$this->cur = NULL;
		}
		else
		{
			if ($this->cur->GetSibling())
			{
				$p = $this->cur->GetSibling();
				while ($p->GetChild())
				{
					array_push($this->stack, $p);
					$p = $p->GetChild();
				}
				$this->cur = $p;
			}
			else
			{
				$this->cur = array_pop($this->stack);
			}
		}
		return $this->cur;
	}
}


//--------------------------------------------------------------------------------------------------
class PreorderIterator extends NodeIterator
{
	//----------------------------------------------------------------------------------------------
	function Begin()
	{
		$this->cur = $this->root;
		return $this->cur;	
	}
	
	//----------------------------------------------------------------------------------------------
	function Next()
	{
		if ($this->cur->GetChild())
		{
			array_push($this->stack, $this->cur);
			$this->cur = $this->cur->GetChild();
		}
		else
		{
			while (!empty($this->stack)
				&& ($this->cur->GetSibling() == NULL))
			{
				$this->cur = array_pop($this->stack);
			}
			if (empty($this->stack))
			{
				$this->cur = NULL;
			}
			else
			{
				$this->cur = $this->cur->GetSibling();
			}
		}
		return $this->cur;
	}
	
}

?>