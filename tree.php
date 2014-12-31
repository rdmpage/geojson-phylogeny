<?php

require_once(dirname(__FILE__) . '/node.php');

define('CHILD', 0);
define ('SIB', 1);

//--------------------------------------------------------------------------------------------------
function write_nexus_label($label)
{
	if (preg_match('/^(\w|\d)+$/', $label))
	{
	}
	else
	{
		str_replace ("'", "\'", $label);
		$label = "'" . $label . "'";
	}
	return $label;
}



//--------------------------------------------------------------------------------------------------
/**
 *
 *
 */
class Tree
{
	var $root;
	var $num_nodes;
	var $label_to_node_map = array();
	var $nodes = array();	
	var $num_leaves;
	var $rooted = true;
	var $has_edge_lengths = false;

	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->root = NULL;;
		$this->num_nodes = 0;
		$this->num_leaves = 0;
	}	
	
	//----------------------------------------------------------------------------------------------
	function GetNumLeaves() { return $this->num_leaves; }
	
	
	//----------------------------------------------------------------------------------------------
	function GetRoot() { return $this->root; }

//----------------------------------------------------------------------------------------------
	function HasBranchLengths() { return $this->has_edge_lengths; }

	//----------------------------------------------------------------------------------------------
	function IsRooted() { return $this->rooted; }
	
	//----------------------------------------------------------------------------------------------
	function SetRoot($root)
	{
		$this->root = $root;
	}
	
	//----------------------------------------------------------------------------------------------
	function NodeWithLabel($label)
	{
		//print_r(array_keys($this->label_to_node_map));
	
		$p = NULL;
		if (in_array($label, array_keys($this->label_to_node_map)))
		{
			$p = $this->label_to_node_map[$label];
		}
		return $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function NewNode($label = '')
	{
		$node = new Node($label);
		$node->id = $this->num_nodes++;
		$this->nodes[$node->id] = $node;
		if ($label != '')
		{
			$this->label_to_node_map[$label] = $node->id;
		}
		else
		{
			/*$label = "_" . $node->id;
			$node->SetLabel($label);
			$this->label_to_node_map[$label] = $node->id;*/
		}		
		return $node;
	}
	
	//----------------------------------------------------------------------------------------------
	function Parse ($str)
	{

		$str = str_replace('\\', "", $str);
		
		$str = str_replace("(", "|(|", $str);
		$str = str_replace(")", "|)|", $str);
		$str = str_replace(",", "|,|", $str);
		$str = str_replace(":", "|:|", $str);
		$str = str_replace(";", "|;|", $str);
		$str = str_replace("||", "|", $str);
		
		$token = explode("|", $str);
		
		//print_r($token);
		
		$curnode = $this->NewNode();
		$this->root = $curnode;

		$state = 0;
		$stack = array();
		$n = count($token);
		
		$i = 1;
		while ($state != 99)
		{
			switch ($state)
			{
				case 0: // getname
					if (ctype_alnum($token[$i]{0}))
					{
						$this->num_leaves++;
						
						$label = $token[$i];
						
	
						// kml
						if (preg_match('/^(?<label>.*)\s*lat=(?<lat>.*)long=(?<long>.*)$/Uu', $label, $m))
						{
							$curnode->SetAttribute('lat', $m['lat']);
							$curnode->SetAttribute('long', $m['long']);
							$label = $m['label'];
						}
						
						$curnode->SetLabel($label);
						$this->label_to_node_map[$label] = $curnode;
						
						
						$i++;
						$state = 1;
					}
					else 
					{
						if ($token[$i]{0} == "'")
						{
							$label = $token[$i];
							$label = preg_replace("/^'/", "", $label);
							$label = preg_replace("/'$/", "", $label);
							$this->num_leaves++;
							
							
							// kml
							if (preg_match('/^(?<label>.*)\s*lat=(?<lat>.*)long=(?<long>.*)$/Uu', $label, $m))
							{
								$curnode->SetAttribute('lat', $m['lat']);
								$curnode->SetAttribute('long', $m['long']);
								$label = $m['label'];
							}
							
							$curnode->SetLabel($label);
							$this->label_to_node_map[$label] = $curnode;
							
							
							$i++;
							$state = 1;
							
						}
						else
						{
							switch ($token[$i])
							{
								case '(':
									$state = 2;
									break;
								default:
									$state = 99;
									break;
							}
						}
					}
					break;
					
				case 1: // getinternode
					switch ($token[$i])
					{
						case ':':
						case ',':
						case ')':
							$state = 2;
							break;
						default:
							$state = 99;
							break;
					}
					break;
					
				case 2: // nextmove
					switch ($token[$i])
					{
						case ':':
							$i++;
							if (is_numeric($token[$i]))
							{
								$curnode->SetAttribute('edge_length', $token[$i]);
								$this->has_edge_lengths = true;
								$i++;
							}
							break;
						case ',':
							$q = $this->NewNode();
							$curnode->SetSibling($q);
							$c = count($stack);
							if ($c == 0)
							{
								$state = 99;
							}
							else
							{
								$q->SetAncestor($stack[$c - 1]);
								$curnode = $q;
								$state = 0;
								$i++;
							}
							break;							
						case '(':
							$stack[] = $curnode;
							$q = $this->NewNode();
							$curnode->SetChild($q);
							$q->SetAncestor($curnode);
							$curnode = $q;
							$state = 0;
							$i++;
							break;
						case ')':
							if (empty($stack))
							{
								$state = 99;
							}
							else
							{
								$curnode = array_pop($stack);
								$state = 3;
								$i++;
							}
							/*
							$c = count($stack);
							if ($c == 0)
							{
								$state = 99;
							}
							else
							{
								$q = $stack[$c - 1];
								$curnode = $q;
								array_pop($stack);
								$state = 3;
								$i++;
							}*/
							break;
						
						case ';':
							if (empty($stack))
							{
								$state = 99;
							}
							else
							{
								$state = 99;
							}
							/*
							$c = count($stack);
							if ($c == 0)
							{
								$state = 99;
							}
							else
							{
								$state = 99;
							} */
							break;
						
						default:
							$state = 99;
							break;
					}
					break;
				
				case 3: // finishchildren
					if (ctype_alnum($token[$i]{0}))
					{
						$curnode->SetLabel($token[$i]);
						$this->label_to_node_map[$token[$i]] = $curnode;
						$i++;
					}
					else
					{
						switch ($token[$i])
						{
							case ':':
								$i++;
								if (is_numeric($token[$i]))
								{
									$curnode->SetAttribute('edge_length', $token[$i]);
									$this->has_edge_lengths = true;
									$i++;
								}
								break;
							case ')':
								$c = count($stack);
								if ($c == 0)
								{
									$state = 99;
								}
								else
								{
									$q = $stack[$c - 1];
									$curnode = $q;
									array_pop($stack);
									$i++;
								}
								break;
							case ',':
								$q = $this->NewNode();
								$curnode->SetSibling($q);
								$c = count($stack);
								if ($c == 0)
								{
									$state = 99;
								}
								else
								{
									$q->SetAncestor($stack[$c - 1]);
									$curnode = $q;
									$state = 0;
									$i++;
								}
								break;
							case ';':
								$state = 2;
								break;
							default:
								$state = 99;
								break;
						}
					}
					break;
			}
		}
						
	}		
						
	//----------------------------------------------------------------------------------------------
	function Dump()
	{
		//echo "label_to_node_map\n";
		//print_r($this->label_to_node_map);
		
		//foreach ($this->nodes as $node)
		//{
		//	echo $node->GetLabel() . "\n";
		//}
		
		echo "Num leaves = " . $this->num_leaves . "\n";
		
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			//echo "Node=\n:";
			$a->Dump();
			$a = $n->Next();
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function WriteDot()
	{
		$dot = "digraph{\n";
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			if ($a->GetAncestor())
			{
				$dot .= "\"" . $a->GetAncestor()->GetLabel() . "\" -> \"" . $a->GetLabel() . "\";\n";
			}
			$a = $n->Next();
		}
		$dot .= "}\n";
		return $dot;
	}
		
	//----------------------------------------------------------------------------------------------
	function WriteNewick()
	{
		$newick = '';
		
		$stack = array();
		$curnode = $this->root;
		
		while ($curnode != NULL)
		{	
			if ($curnode->GetChild())
			{
				$newick .= '(';
				$stack[] = $curnode;
				$curnode = $curnode->GetChild();
			}
			else
			{
				$newick .= write_nexus_label($curnode->GetLabel());
				
				$length = $curnode->GetAttribute('edge_length');
				if ($length != '')
				{
					$newick .= ':' . $length;
				}
											
				while (!empty($stack) && ($curnode->GetSibling() == NULL))
				{
					$newick .= ')';
					$curnode = array_pop($stack);
					
					// Write internal node
					if ($curnode->GetLabel() != '')
					{
						$newick .= write_nexus_label($curnode->GetLabel());
					}
					$length = $curnode->GetAttribute('edge_length');
					if ($length != '')
					{
						$newick .= ':' . $length;
					}					

				}
				if (empty($stack))
				{
					$curnode = NULL;
				}
				else
				{
					$newick .= ',';
					$curnode = $curnode->GetSibling();
				}
			}		
		}
		$newick .= ";";
		return $newick;
	}	
			
	
	//----------------------------------------------------------------------------------------------
	// Build weights
	function BuildWeights($p)
	{
		if ($p)
		{
			$p->SetAttribute('weight', 0);
			
			$this->BuildWeights($p->GetChild());
			$this->BuildWeights($p->GetSibling());
			
			if ($p->Isleaf())
			{
				$p->SetAttribute('weight', 1);
			}
			if ($p->GetAncestor())
			{
				$p->GetAncestor()->AddWeight($p->GetAttribute('weight'));
			}
		}
	}


}

?>