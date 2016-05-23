<?php
namespace App\Libraries;

/**
 * LUA data table parser
 *
 * This file contains the code that parses LUA data tables into a PHP array.
 * Mostly used when extracting information from World of Warcraft addons.
 *
 * @author David Stangeby <david.stangeby@gmail.com>
 * @version 0.1
 * @package WLP
 * @link http://fin.instinct.org/lua/ The code this class was based of
 * @copyright  Copyright (c) 2005-2009 Arctic Solutions (http://www.arcticsolutions.no/)
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU General Public License v2
 */

/**
 * Class that parses the LUA data file file
 *
 * @package WLP
 */
 
use Exception;
 
class LuaParser {
	private $source;
	private $source_len;
	
	private $offset = 0;
	private $line = 1;
	private $EOF = false;
	private $stack = array();
	
	private $exports = array();
	
	// Interface ---------------------------------------------------------------
	
	public function __construct($lua) {
		$this->source = $lua;
		$this->source_len = strlen($lua);
	}
	
	// Cleaning code before parse
	private function preparse() {
		// Remove comments
		//$this->_source = preg_replace("/--.*$/m", "", $this->_source); [<- BROKEN]
	}
	
	// Parse and return PHP
	public function parse() {
		$this->preparse();
		
		while(!$this->isEOF()) {
			$operands = $this->parseRoot();
			$this->exports[$operands[0]] = $operands[1];
		}
		
		return $this->exports;
	}
	
	// Navigators --------------------------------------------------------------
	
	// <-
	private function &back(&$value = null) {
		if($this->offset-1 >= 0) {
			$char = $this->source[--$this->offset];
			if($char == "\n") $this->line--;
		}
		
		return $value;
	}
	
	// v
	private function char() {
		return $this->source[$this->offset];
	}
	
	// ->
	private function next($count = 1) {
		if($this->offset+1 >= strlen($this->source)) {
			$this->EOF = true;
			return "";
		}
		
		$buffer = "";
		while($count-- && !$this->EOF) {
			$buffer .= ($char = $this->source[$this->offset++]);
			if($char == "\n") $this->line++;
		}
		
		$this->checkEOF();
		
		return $buffer;
	}
	
	// Stack -------------------------------------------------------------------
	
	private function save() {
		array_push($this->stack, array($this->offset, $this->line, $this->EOF));
	}
	
	private function delete() {
		array_pop($this->stack);
	}
	
	private function restore() {
		$ctx = array_pop($this->stack);
		$this->offset = $ctx[0];
		$this->line = $ctx[1];
		$this->EOF = $ctx[2];
	}
	
	// Buffer ------------------------------------------------------------------
	
	private function pos() {
		return $this->offset;
	}
	
	private function slice($begin, $end) {
		$len = ($end-1)-$begin;
		return substr($this->source, $begin, $end-$begin);
	}
	
	// Tools -------------------------------------------------------------------
	
	// Parse error
	private function error($error) {
		throw new Exception($error." (got '".$this->char()."') on line ".$this->line);
	}
	
	private function errorBack($error) {
		$this->back();
		$this->error($error);
	}
	
	private function checkEOF() {
		if($this->EOF) {
			$this->errorBack("unexpected end-of-file");
		}
	}
	
	// Eat whitepaces
	private function whitespaces() {
		$whitespaces = array(" ", "\t", "\n", "\r");
		while(in_array($this->next(), $whitespaces) && !$this->EOF);
		$this->back();
		
		if($this->EOF) {
			return;
		}
		
		$this->save();
		if($this->next(2) == "--") {
			$this->restore();
			$this->comments();
			$this->whitespaces();
		} else {
			$this->restore();
		}
	}
	
	// Eat comments
	private function comments() {
		if($this->next() == "-" && $this->next() == "-") {
			// Multi-line
			if($this->next() == "[" && $this->next() == "[") {
				while($char = $this->next() && !$this->EOF) {
					if($char == "-") {
						$this->save();
						if($this->next(3) == "-]]") {
							$this->delete();
							break;
						}
						$this->restore();
					}
				}
				
				$this->checkEOF();
			} else {
				// Single line
				while($this->next() != "\n" && !$this->EOF);
			}
		} else {
			$this->errorBack("comment expected");
		}
	}
	
	// Parsers -----------------------------------------------------------------
	
	// Parse a root assignment
	private function parseRoot() {
		$ident = $this->parseIdent();
		$this->parseEqual();
		$value = $this->parseValue();
		
		return array($ident, $value);
	}
	
	// Parse a root identifier
	private function parseIdent() {
		static $identChars = false;
		if(!$identChars) {
			$identChars = str_split("abcdefghijklmnopqrstuvwxyz0123456789_");
		}
		
		$this->whitespaces();
		
		$start = $this->pos();
		while(in_array(strtolower($this->next()), $identChars) && !$this->EOF);
		$this->back();
		
		$this->checkEOF();
		
		$ident = $this->slice($start, $this->pos());
		
		if($ident == "") {
			$this->errorBack("identifier expected");
		}
		
		return $ident;
	}
	
	// Parse a '='
	private function parseEqual() {
		$this->whitespaces();
		if($this->next() != "=") {
			$this->errorBack("equal expected");
		}
	}
	
	// Parse a ','
	private function parseComma() {
		$this->whitespaces();
		if($this->next() != ",") {
			$this->errorBack("comma expected");
		}
	}
	
	// Parse a Lua value
	private function parseValue($scalar = false) {
		static $numChars = false;
		if(!$numChars) {
			$numChars = str_split("0123456789+-ex.");
		}
		
		$this->whitespaces();
		
		switch($char = $this->char()) {
			case "t":
				if($scalar) {
					$this->error("invalid scalar value");
				}
				if($this->next(4) != "true") {
					$this->error("invalid boolean true");
				}
				return true;
				
			case "f":
				if($scalar) {
					$this->error("invalid scalar value");
				}
				if($this->next(5) != "false") {
					$this->error("invalid boolean false");
				}
				return false;
				
			case "n":
				if($scalar) {
					$this->error("invalid scalar value");
				}
				if($this->next(3) != "nil") {
					$this->error("invalid nil");
				}
				return null;
				
			case "{":
				if($scalar) {
					$this->error("invalid scalar value");
				}
				$this->next();
				return $this->parseObject();
				
			case "'":
			case '"':
				return $this->parseString();
				
			default:
				// Number
				if(!in_array($char, $numChars)) {
					$this->error("value expected");
				}
				
				$start = $this->pos();
				while(in_array(strtolower($this->next()), $numChars) && !$this->EOF);
				$this->back();
				
				$this->checkEOF();
				
				$number = $this->slice($start, $this->pos());
				
				if(!is_numeric($number)) {
					$this->errorBack("number expected");
				}
				
				return ($number*1);
		}
	}
	
	// Parse an object
	private function parseObject() {
		$obj = array();
		
		try {
			while(true && !$this->EOF) {
				try {
					$this->save();
					$key = $this->parseKey();
					$this->parseEqual();
				} catch(Exception $e) {
					$this->restore();
					$key = false;
				}
				
				$value = $this->parseValue();
				
				if($key) {
					$obj[$key] = $value;
				} else {
					$obj[] = $value;
				}
				
				$this->parseComma();
			}
			
			$this->checkEOF();
		} catch(Exception $e) {
			try {
				$this->parseCloseBrace();
			} catch(Exception $e2) {
				throw $e;
			}
			return $obj;
		}
	}
	
	// Parse a object key
	private function parseKey() {
		$this->whitespaces();
		
		$this->parseOpenBracket();
		$key = $this->parseValue(true);
		$this->parseCloseBracket();
		
		return $key;
	}
	
	// Parse a string
	private function parseString() {
		$this->whitespaces();
		
		$delimiter = $this->next();
		
		if($delimiter != "'" && $delimiter != '"') {
			$this->errorBack("invalid string delimiter");
		}
		
		$start = $this->pos();
		while(($char = $this->next()) != $delimiter && !$this->EOF) {
			if($char == '\\') {
				$this->next();
			}
		}
		
		$this->checkEOF();
		
		return $this->slice($start, $this->pos()-1);
	}
	
	// Parse a '['
	private function parseOpenBracket() {
		$this->whitespaces();
		if($this->next() != "[") {
			$this->errorBack("open bracket expected");
		}
	}
	
	// Parse a ']'
	private function parseCloseBracket() {
		$this->whitespaces();
		if($this->next() != "]") {
			$this->errorBack("close bracket expected");
		}
	}
	
	// Parse '}'
	private function parseCloseBrace() {
		$this->whitespaces();
		if($this->next() != "}") {
			$this->errorBack("close brace expected");
		}
	}
	
	// Lookahead EOF
	private function isEOF() {
		$this->whitespaces();
		return $this->EOF;
	}
}

function parse_lua($lua) {
	$parser = new LuaParser($lua);
	return $parser->parse();
}