<?php


require_once (dirname(__FILE__) . '/nexus.php');

//--------------------------------------------------------------------------------------------------
// Read NEXUS file with geographic coordinates and (optionally) taxon assignments

function read_nexus($str)
{
	$data = new stdclass;
	
	$nx = new NexusReader($str);

	if ($nx->IsNexusFile()) {}; // echo "Is NEXUS file\n";
	
	$blockname = $nx->GetBlock();
		
	if ($blockname == 'taxa')
	{
		$command = $nx->GetCommand();
		
		while ( 
			(($command != 'end') && ($command != 'endblock'))
			&& ($nx->error == NexusError::ok)
			)
		{		
			switch ($command)
			{
				case 'taxlabels':
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;	
					
				default:
					echo "Command to skip: $command\n";
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
			}
			
			// If end command eat the semicolon
			if (($command == 'end') || ($command == 'endblock'))
			{
				$nx->GetToken();
			}
		}
		$blockname = $nx->GetBlock();
					
	}
	
	// trees	
	if ($blockname == 'trees')
	{
		$data->treeblock = new stdclass;
		
		$command = $nx->GetCommand();
		
		while ( 
			(($command != 'end') && ($command != 'endblock'))
			&& ($nx->error == NexusError::ok)
			)
		{
			//echo "Command=$command\n";
			
			switch ($command)
			{
				case 'translate':
					if (!$data->treeblock->translations)
					{
						$data->treeblock->translations = new stdclass;
					}
					$data->treeblock->translations->translate = array();
					
					$done = false;
					while (!$done && ($nx->error == NexusError::ok))
					{
						$t = $nx->GetToken();
						
						if (in_array($t, array(TokenTypes::Number, TokenTypes::String, TokenTypes::QuotedString)))
						{
							$otu = $nx->buffer;
							$t = $nx->GetToken();
							
							if (in_array($t, array(TokenTypes::Number, TokenTypes::String, TokenTypes::QuotedString)))
							{
								$data->treeblock->translations->translate[$otu] = $nx->buffer;
								
								$t = $nx->GetToken();
								switch ($t)
								{
									case TokenTypes::Comma:
										break;
										
									case TokenTypes::SemiColon:
										$done = true;
										break;
										
									default:
										$nx->error = NexusError::syntax;
										break;
								}
							}
							else
							{
								$nx->error = NexusError::syntax;
							}
						}
						else
						{
							$nx->error = NexusError::syntax;
						}
					}					
					
					$command = $nx->GetCommand();
					break;
					
				case 'tree':	
					if ($command == 'tree')
					{
						if (!isset($data->treeblock->trees))
						{
							$data->treeblock->trees = array();
						}
						$tree = new stdclass;
						
						$t = $nx->GetToken();
						if ($t == TokenTypes::Asterix)
						{
							$tree->default = true;
							$t = $nx->GetToken();
						}
						if ($t == TokenTypes::String)
						{
							$tree->label = $nx->buffer;
						}
						$t = $nx->GetToken();
						if ($t == TokenTypes::Equals)
						{
							$tree->newick = '';
							$t = $nx->GetToken();
							while ($t != TokenTypes::SemiColon)
							{
								if ($t == TokenTypes::QuotedString)
								{
									$s = $nx->buffer;
									$s = str_replace("'", "''", $s);
									$s = "'" . $s . "'";
									$tree->newick .= $s;
								}
								else
								{
									$tree->newick .= $nx->buffer;
								}
								$t = $nx->GetToken();
							}
							$tree->newick .= ';';
							
							$data->treeblock->trees[] = $tree;
						}
						
					}				
					$command = $nx->GetCommand();
					break;
	
				default:
					//echo "Command to skip: $command\n";
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
			}
			
			// If end command eat the semicolon
			if (($command == 'end') || ($command == 'endblock'))
			{
				$nx->GetToken();
			}
			
			
		}
		
		$blockname = $nx->GetBlock();
	
	}
	
	//exit();
	//echo __LINE__ . "\n";

	if ($blockname == 'characters')
	{		
		$data->characters = new stdclass;
		
		$command = $nx->GetCommand();
		
		while ( 
			(($command != 'end') && ($command != 'endblock'))
			&& ($nx->error == NexusError::ok)
			)
		{			
			switch ($command)
			{
				case 'charstatelabels':
					$data->characters->charstatelabels = array();
					
					$done = false;
					while (!$done && ($nx->error == NexusError::ok))
					{
						$t = $nx->GetToken();
						
						if ($t == TokenTypes::Number)
						{
							$index = (Integer)$nx->buffer - 1;
						
							$t = $nx->GetToken();
							
							if (in_array($t, array(TokenTypes::Number, TokenTypes::String, TokenTypes::QuotedString)))
							{
								$data->characters->charstatelabels[$index] = strtolower($nx->buffer);
								
								$t = $nx->GetToken();
								switch ($t)
								{
									case TokenTypes::Comma:
										break;
										
									case TokenTypes::SemiColon:
										$done = true;
										break;
										
									default:
										$nx->error = NexusError::syntax;
										break;
								}
							}
							else
							{
								$nx->error = NexusError::syntax;
							}
						}
						else
						{
							$nx->error = NexusError::syntax;
						}
					}					
					
					$command = $nx->GetCommand();
					break;
					
				case 'dimensions':
					$t = $nx->GetToken();
					if ($t == TokenTypes::String)
					{
						if (strtolower($nx->buffer) == 'nchar')
						{
							$t = $nx->GetToken();
							if ($t == TokenTypes::Equals)
							{
								$t = $nx->GetToken();
								if ($t == TokenTypes::Number)
								{
									$data->characters->nchar = $nx->buffer;
								}
							}
						}
					}
					$t = $nx->GetToken();
					$command = $nx->GetCommand();
					break;	
					
				case 'format':
					$t = $nx->GetToken();
					if ($t == TokenTypes::String)
					{
						if (strtolower($nx->buffer) == 'datatype')
						{
							$t = $nx->GetToken();
							if ($t == TokenTypes::Equals)
							{
								$t = $nx->GetToken();
								if ($t == TokenTypes::String)
								{
									$data->characters->datatype = strtolower($nx->buffer);
								}
							}
						}
					}
					$t = $nx->GetToken();
					$command = $nx->GetCommand();
					break;					
			
				case 'matrix':
					$data->characters->matrix = array();
					
					$counter = 0;
					$current_row = '';
					
					$sign = 1.0;
					
					$done = false;
					while (!$done && ($nx->error == NexusError::ok))
					{
						$t = $nx->GetToken();
						
						switch ($t)
						{
							case TokenTypes::Number:
							case TokenTypes::String:
							case TokenTypes::QuotedString:
							
								if ($counter == 0)
								{
									$data->characters->matrix[$nx->buffer] = array();
									$current_row = $nx->buffer;
								}
								else
								{			
									$value = $sign * $nx->buffer;
									$sign = 1.0;
									
									if (isset($data->characters->charstatelabels))
									{
										if (isset($data->characters->charstatelabels[$counter - 1]))
										{
											$data->characters->matrix[$current_row][$data->characters->charstatelabels[$counter - 1]] = $value;
										}
									}
									else
									{
										$data->characters->matrix[$current_row][] = $value;
									}
									
								}
								$counter++;
								if ($counter > $data->characters->nchar)
								{
									$counter = 0;
								}							
								break;
								
							case TokenTypes::Minus:
								$sign = -1.0;
								break;								
														
							case TokenTypes::SemiColon:
								$done = true;
								break;
								
							default:
								$nx->error = NexusError::syntax;
								break;
						}
					}					
					
					$command = $nx->GetCommand();				
					break;
			
				default:
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
			}
			
			// If end command eat the semicolon
			if (($command == 'end') || ($command == 'endblock'))
			{
				$nx->GetToken();
			}
			
			
		}
		
		$blockname = $nx->GetBlock();
	}
	
	// notes
	if ($blockname == 'notes')
	{		
		$data->notes = new stdclass;
		
		$command = $nx->GetCommand();
		
		while ( 
			(($command != 'end') && ($command != 'endblock'))
			&& ($nx->error == NexusError::ok)
			)
		{			
			switch ($command)
			{
				case 'alttaxnames':
					if (!isset($data->notes->alttaxnames))
					{
						$data->notes->alttaxnames = array();
					}
					$alttaxnames = new stdclass;
					$alttaxnames->names = array();
					
					$t = $nx->GetToken();
					if ($t == TokenTypes::Asterix)
					{
						$alttaxnames->default = true;
						$t = $nx->GetToken();
					}
					if ($t == TokenTypes::String)
					{
						$alttaxnames->label = $nx->buffer;
					}
					$t = $nx->GetToken();
					if ($t == TokenTypes::Equals)
					{
						$t = $nx->GetToken();
						while ($t != TokenTypes::SemiColon)
						{
							$alttaxnames->names[] = $nx->buffer;
							$t = $nx->GetToken();
						}
						
						$data->notes->alttaxnames[] = $alttaxnames;
					}						
					
					$command = $nx->GetCommand();
					break;
					
				default:
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
			}
			
			// If end command eat the semicolon
			if (($command == 'end') || ($command == 'endblock'))
			{
				$nx->GetToken();
			}
			
			
		}
		
		$blockname = $nx->GetBlock();
	}	
	
	
	
	//echo "Error=" . $nx->error . "\n";
	return $data;
}


?>