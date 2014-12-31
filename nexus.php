<?php

// Very basic NEXUS parser

define(NEXUSPunctuation, "()[]{}/\\,;:=*'\"`+-");
define(NEXUSWhiteSpace, "\n\r\t ");

//--------------------------------------------------------------------------------------------------
class TokenTypes
{
	const None 			= 0;
	const String 		= 1;
	const Hash 			= 2;
	const Number 		= 3;
	const SemiColon 	= 4;
	const OpenPar		= 5;
	const ClosePar 		= 6;
	const Equals 		= 7;
	const Space 		= 8;
	const Comma  		= 9;
	const Asterix 		= 10;
	const Colon 		= 11;
	const Other 		= 12;
	const Bad 			= 13;
	const Minus 		= 14;
	const DoubleQuote 	= 15;
	const Period 		= 16;
	const Backslash 	= 17;
	const QuotedString	= 18;
}

//--------------------------------------------------------------------------------------------------
class NumberTokens
{
	const start 		= 0;
	const sign 			= 1;
	const digit 		= 2;
	const fraction 		= 3;
	const expsymbol 	= 4;
	const expsign 		= 5;
	const exponent 		= 6;
	const bad 			= 7;
	const done 			= 8;
}

//--------------------------------------------------------------------------------------------------
class StringTokens
{
	const ok 			= 0;
	const quote 		= 1;
	const done 			= 2;
}

//--------------------------------------------------------------------------------------------------
class NexusError
{
	const ok 			= 0;
	const nobegin 		= 1;
	const noend 		= 2;
	const syntax 		= 3;
	const badcommand 	= 4;
	const noblockname 	= 5;
	const badblock	 	= 6;
	const nosemicolon	= 7;
}

//--------------------------------------------------------------------------------------------------
class Scanner
{
	public $error = 0;
	public $comment = '';
	public $pos = 0;
	public $str = '';
	public $token = TokenTypes::None;
	public $buffer = '';
	
	//----------------------------------------------------------------------------------------------
	function __construct($str)
	{
		$this->str = $str;
	}

	//----------------------------------------------------------------------------------------------
	function GetToken($returnspace = false)
	{		
		$this->token = TokenTypes::None;
		while (($this->token == TokenTypes::None) && ($this->pos < strlen($this->str)))
		{
			//echo "+" . $this->str{$this->pos} . "\n";
			if (strchr(NEXUSWhiteSpace, $this->str{$this->pos}))
			{
				if ($returnspace && ($this->str{$this->pos} == ' '))
				{
					$this->token = TokenTypes::Space;
				}
			}
			else
			{
				if (strchr (NEXUSPunctuation, $this->str{$this->pos}))
				{
					$this->buffer = $this->str{$this->pos};
					//echo "-" . $this->str{$this->pos} . "\n";
 					switch ($this->str{$this->pos})
 					{
 						case '[':
 							$this->ParseComment();
 							break;
 						case "'":
 							if ($this->ParseString())
 							{
 								$this->token = TokenTypes::QuotedString;
 							}
 							else
 							{
 								$this->token = TokenTypes::Bad;
 							}
 							break;
						case '(':
							$this->token = TokenTypes::OpenPar;
							break;
						case ')':
							$this->token = TokenTypes::ClosePar;
							break;
						case '=':
							$this->token = TokenTypes::Equals;
							break;
						case ';':
							$this->token = TokenTypes::SemiColon;
							break;
						case ',':
							$this->token = TokenTypes::Comma;
							break;
						case '*':
							$this->token = TokenTypes::Asterix;
							break;
						case ':':
							$this->token = TokenTypes::Colon;
							break;
						case '-':
							$this->token = TokenTypes::Minus;
							break;
						case '"':
							$this->token = TokenTypes::DoubleQuote;
							break;
					   	case '/':
							$this->token = TokenTypes::BackSlash;
							break;
						default:
							$this->token = TokenTypes::Other;
							break;
					}
				}
				else
				{
					if ($this->str{$this->pos} == '#')
					{
						$this->token = TokenTypes::Hash;

					}
					else if ($this->str{$this->pos} == '.')
					{
						$this->token = TokenTypes::Period;
					}
					else
					{
						if (is_numeric($this->str{$this->pos}))
						{
							if ($this->ParseNumber())
							{
								$this->token = TokenTypes::Number;
							}
							else
							{
								$this->token = TokenTypes::Bad;
							}
						}
						else
						{
							if ($this->ParseToken())
							{
								$this->token = TokenTypes::String;
							}
							else
							{
								$this->token = TokenTypes::Bad;
							}
						}
					}
				}
			}
			$this->pos++;			

		}
		return $this->token;
	}
	
	
	//----------------------------------------------------------------------------------------------
	function ParseComment()
	{
		$this->buffer = '';
		
		while (($this->str{$this->pos} != ']') && ($this->pos < strlen($this->str)))
		{
			$this->buffer .= $this->str{$this->pos};
			$this->pos++;
		}
		$this->buffer .= $this->str{$this->pos};
	}

	//----------------------------------------------------------------------------------------------
	function ParseNumber()
	{
		$this->buffer = '';
		$state = NumberTokens::start;
		
		while (
			($this->pos < strlen($this->str))
			&& (!strchr (NEXUSWhiteSpace, $this->str{$this->pos}))
			&& (!strchr (NEXUSPunctuation, $this->str{$this->pos}))
			&& ($this->str{$this->pos} != '-')
			&& ($state != NumberTokens::bad)
			&& ($state != NumberTokens::done)
			)
		{
			if (is_numeric($this->str{$this->pos}))
			{
				switch ($state)
				{
					case NumberTokens::start:
					case NumberTokens::sign:
						$state =  NumberTokens::digit;
						break;
					case NumberTokens::expsymbol:
					case NumberTokens::expsign:
						$state =  NumberTokens::exponent;
						break;
					default:
						break;
				}
			}
			else if (($this->str{$this->pos} == '-') && ($this->str{$this->pos} == '+'))
			{
				switch ($state)
				{
					case NumberTokens::start:
						$state = NumberTokens::sign;
						break;
					case NumberTokens::digit:
						$state = NumberTokens::done;
						break;
					case NumberTokens::expsymbol:
						$state = NumberTokens::expsign;
						break;
					default:
						$state = NumberTokens::bad;
						break;
				}
			}
			else if (($this->str{$this->pos} == '.') && ($state == NumberTokens::digit))
			{
				$state = NumberTokens::fraction;
			}
			else if ((($this->str{$this->pos} == 'E') || ($this->str{$this->pos} == 'e')) && (($state == NumberTokens::digit) || ($state == NumberTokens::fraction)))			
			{
				$state = NumberTokens::expsymbol;
			}
			else
			{
				$state = NumberTokens::bad;
			}
			
			if (($state != NumberTokens::bad) && ($state != NumberTokens::done))
			{
				$this->buffer .= $this->str{$this->pos};
				$this->pos++;
			}
		}
		$this->pos--;
		return true; 		
	}
	
	//----------------------------------------------------------------------------------------------
	function ParseString()
	{
		//echo "ParseString\n";
		$this->buffer = '';
		
		$this->pos++;
		
		$state = StringTokens::ok;
		while ($state != StringTokens::done)
		{
			//echo "--" . $this->str{$this->pos} . "\n";
			
			switch ($state)
			{
				case StringTokens::ok:
					if ($this->str{$this->pos} == "'")
					{
						$state = StringTokens::quote;
					}
					else
					{
						$this->buffer .= $this->str{$this->pos};
					}
					break;
					
				case StringTokens::quote:
					if ($this->str{$this->pos} == "'")
					{
						$this->buffer .= $this->str{$this->pos};
						$state = StringTokens::ok;
					}
					else
					{
						$state = StringTokens::done;
						$this->pos--;
					}
					break;
					
				default:
					break;
			}			
			$this->pos++;
		}
		$this->pos--;
		return true;
	}
	

	//----------------------------------------------------------------------------------------------
	function ParseToken()
	{
		$this->buffer = '';
		
		while (
			($this->pos < strlen($this->str))
			&& (!strchr (NEXUSWhiteSpace, $this->str{$this->pos}))
			&& (!strchr (NEXUSPunctuation, $this->str{$this->pos}))
			)
		{
			$this->buffer .= $this->str{$this->pos};
			$this->pos++;
		}
		$this->pos--;
		return true;
	}
	
}

//--------------------------------------------------------------------------------------------------
class NexusReader extends Scanner
{
	public $nexusCommands = array('alttaxnames', 'begin', 'charstatelabels', 'dimensions', 'end', 'endblock', 'format', 'link', 'matrix', 'taxa', 'taxlabels', 'title', 'translate', 'tree');
	public $nexusBlocks = array('taxa', 'trees');
	
	//----------------------------------------------------------------------------------------------
	function GetBlock()
	{
		$blockname = '';
		
		//echo __LINE__ . " get block\n";
		
		$command =  $this->GetCommand();
		if ($command != 'begin')
		{
			$this->error = NexusError::nobegin;
		}
		else
		{
			// get block name
			$t = $this->GetToken();
			if ($t == TokenTypes::String)
			{
				$blockname = strtolower($this->buffer);
				$t = $this->GetToken();
				if ($t != TokenTypes::SemiColon)
				{
					$this->error = NexusError::noblockname;
				}
			}
			else
			{
				$this->error = NexusError::noblockname;
			}
			
		}
		return $blockname;
	}
	
	//----------------------------------------------------------------------------------------------
	function GetCommand()
	{
		$command = '';
		
		//echo __LINE__ . " get command\n";
		
		
		$t = $this->GetToken();
		if ($t == TokenTypes::String)
		{
			if (in_array(strtolower($this->buffer), $this->nexusCommands))
			{
				$command = strtolower($this->buffer);
			}
			else
			{
				$this->error = NexusError::badcommand;
			}
		}
		else
		{
			$this->error = NexusError::syntax;
		}
		return $command;
	}
		
	//----------------------------------------------------------------------------------------------
	function IsNexusFile()
	{
		$this->error = NexusError::ok;
		
		$nexus = false;
		$t = $this->GetToken();
		if ($t == TokenTypes::Hash)
		{
			$t = $this->GetToken();
			if ($t == TokenTypes::String)
			{
				$nexus = (strcasecmp('NEXUS', $this->buffer) == 0);
			}
		}
		return $nexus;
	}
	
	//----------------------------------------------------------------------------------------------
	function SkipCommand()
	{	
		do {
			$t = $this->GetToken();
		} while (($this->error == NexusError::ok) && ($t != TokenTypes::SemiColon));
		return $this->error;
	}

}


?>