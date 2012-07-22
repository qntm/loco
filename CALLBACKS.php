<?php
	/* need a callback for every last rule! */
	function topobject($whitespace, $object) { return $object; }
	function object($left_brace, $whitespace, $objectcontent, $right_brace, $whitespace) { return $objectcontent; }
	function fullobject($keyvalue, $commakeyvaluelist) {
		$commakeyvaluelist[$keyvalue[0]] = $keyvalue[1];
		return $commakeyvaluelist;
	}
	function objectcontent($value) { return $value; }
	function emptyobject() { return array(); }

	function commakeyvaluelist() {
		$commakeyvaluelist = array();
		foreach(func_get_args() as $commakeyvalue) {
			$commakeyvaluelist[$commakeyvalue[0]] = $commakeyvalue[1];
		}
		return $commakeyvaluelist;
	}
	function commakeyvalue($comma, $whitespace, $keyvalue) { return $keyvalue; }
	function keyvalue($string, $colon, $whitespace, $value) { return array($string, $value); }
	function jsonarray($left_bracket, $whitespace, $arraycontent, $right_bracket, $whitespace) { return $arraycontent; }
	function arraycontent($value) { return $value; }

	function fullarray($value, $commavaluelist) {
		array_unshift($commavaluelist, $value);
		return $commavaluelist;
	}
	function emptyarray() { return array(); }
	function commavaluelist() { return func_get_args(); }
	function commavalue($comma, $whitespace, $value) { return $value; }
	function value($value) { return $value; }

	function string($double_quote, $stringcontent, $double_quote, $whitespace) { return $stringcontent; }
	function stringcontent() { return implode("", func_get_args()); }
	function char($char) { return $char; }
	function number($number, $whitespace) { return (float) ($number); }
	function true  ($true,   $whitespace) { return true;    }

	function false ($false,  $whitespace) { return false;   }
	function null  ($null,   $whitespace) { return null;    }
	function WHITESPACE() { return null; }
	function ESCAPED_QUOTE    ($string) { return substr($string, 1, 1); }
	function ESCAPED_BACKSLASH($string) { return substr($string, 1, 1); }

	function ESCAPED_SLASH    ($string) { return substr($string, 1, 1); }
	function ESCAPED_B        ($string) { return "\b"; }
	function ESCAPED_F        ($string) { return "\f"; }
	function ESCAPED_N        ($string) { return "\n"; }
	function ESCAPED_R        ($string) { return "\r"; }

	function ESCAPED_T        ($string) { return "\t"; }
	function ESCAPED_UTF8     ($match ) { return Utf8Combinator::getBytes(hexdec(substr($match, 2, 4))); }

	$parseTree = $jsonParser->parse(" { \"string\" : true, \"\\\"\" : false, \"\\u9874asdh\" : [ null, { }, -9488.44E+093 ] } ");

	var_dump(count($parseTree) === 3);
	var_dump($parseTree["string"] === true);
	var_dump($parseTree["\""] === false);
	var_dump($parseTree["\xE9\xA1\xB4asdh"] === array(null, array(), -9.48844E+96));

?>