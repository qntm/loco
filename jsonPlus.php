<?php
	require_once("Loco.php");
	
	# This code is in the public domain.
	# http://qntm.org/loco

	$jsonPlusGrammar = new Grammar(
		"<topobject>",
		array(
			"<topobject>" => new ConcParser(
				array("WHITESPACE", "<object>"),
				function($whitespace, $object) { return $object; }
			),

			"<object>" => new ConcParser(
				array("LEFT_BRACE", "WHITESPACE", "<objectcontent>", "RIGHT_BRACE", "WHITESPACE"),
				function($left_brace, $whitespace, $objectcontent, $right_brace, $whitespace) { return $objectcontent; }
			),

			"<objectcontent>" => new LazyAltParser(
				array("<fullobject>", "<emptyobject>")
			),

			"<fullobject>" => new ConcParser(
				array("<keyvalue>", "<commakeyvaluelist>"),
				function($keyvalue, $commakeyvaluelist) {
					$commakeyvaluelist[$keyvalue[0]] = $keyvalue[1];
					return $commakeyvaluelist;
				}
			),

			"<emptyobject>" => new EmptyParser(
				function() { return array(); }
			),

			"<commakeyvaluelist>" => new GreedyStarParser(
				"<commakeyvalue>",
				function() {
					$commakeyvaluelist = array();
					foreach(func_get_args() as $commakeyvalue) {
						$commakeyvaluelist[$commakeyvalue[0]] = $commakeyvalue[1];
					}
					return $commakeyvaluelist;
				}
			),

			"<commakeyvalue>" => new ConcParser(
				array("COMMA", "WHITESPACE", "<keyvalue>"),
				function($comma, $whitespace, $keyvalue) { return $keyvalue; }
			),

			"<keyvalue>" => new ConcParser(
				array("<key>", "COLON", "WHITESPACE", "<value>"),
				function($key, $colon, $whitespace, $value) { return array($key, $value); }
			),
			
			"<key>" => new LazyAltParser(
				array("<string>", "<sqstring>")
			),

			"<array>" => new ConcParser(
				array("LEFT_BRACKET", "WHITESPACE", "<arraycontent>", "RIGHT_BRACKET", "WHITESPACE"),
				function($left_bracket, $whitespace, $arraycontent, $right_bracket, $whitespace) { return $arraycontent; }
			),

			"<arraycontent>" => new LazyAltParser(
				array("<fullarray>", "<emptyarray>")
			),

			"<fullarray>" => new ConcParser(
				array("<value>", "<commavaluelist>"),
				function($value, $commavaluelist) {
					array_unshift($commavaluelist, $value);
					return $commavaluelist;
				}
			),

			"<emptyarray>" => new EmptyParser(
				function() { return array(); }
			),

			"<commavaluelist>" => new GreedyStarParser("<commavalue>"),

			"<commavalue>" => new ConcParser(
				array("COMMA", "WHITESPACE", "<value>"),
				function($comma, $whitespace, $value) { return $value; }
			),

			"<value>" => new LazyAltParser(
				array("<string>", "<sqstring>", "<number>", "<object>", "<array>", "<true>", "<false>", "<null>")
			),

			"<string>" => new ConcParser(
				array("DOUBLE_QUOTE", "<stringcontent>", "DOUBLE_QUOTE", "WHITESPACE"),
				function($double_quote, $stringcontent, $double_quote, $whitespace) { return $stringcontent; }
			),

			"<sqstring>" => new ConcParser(
				array("SINGLE_QUOTE", "<sqstringcontent>", "SINGLE_QUOTE", "WHITESPACE"),
				function($single_quote, $sqstringcontent, $single_quote, $whitespace) { return $sqstringcontent; }
			),
			
			"<stringcontent>" => new GreedyStarParser(
				"<char>",
				function() { return implode("", func_get_args()); }
			),

			"<sqstringcontent>" => new GreedyStarParser(
				"<sqchar>",
				function() { return implode("", func_get_args()); }
			),
			
			"<char>" => new LazyAltParser(
				array(
					"UTF8_EXCEPT", "ESCAPED_QUOTE", "ESCAPED_BACKSLASH", "ESCAPED_SLASH", "ESCAPED_B",
					"ESCAPED_F", "ESCAPED_N", "ESCAPED_R", "ESCAPED_T", "ESCAPED_UTF8"
				)
			),
			
			"<sqchar>" => new LazyAltParser(
				array(
					"SQ_UTF8_EXCEPT", "ESCAPED_SINGLE_QUOTE", "ESCAPED_BACKSLASH", "ESCAPED_SLASH", "ESCAPED_B",
					"ESCAPED_F", "ESCAPED_N", "ESCAPED_R", "ESCAPED_T", "ESCAPED_UTF8"
				)
			),

			"<number>" => new ConcParser(array("NUMBER", "WHITESPACE"), function($number, $whitespace) { return $number; }),
			"<true>"   => new ConcParser(array("TRUE",   "WHITESPACE"), function($true,   $whitespace) { return true; }),
			"<false>"  => new ConcParser(array("FALSE",  "WHITESPACE"), function($false,  $whitespace) { return false; }),
			"<null>"   => new ConcParser(array("NULL",   "WHITESPACE"), function($null,   $whitespace) { return null; }),
			
			# actual physical objects (RegexParsers, StringParsers and Utf8Parsers)
			# are represented in all capitals because they are important.
			# this is effectively the lexer portion of the whole shebang.
			
			"WHITESPACE"           => new  RegexParser("#^[ \n\r\t]*#"), // ignored
			"LEFT_BRACE"           => new StringParser("{"),             // ignored
			"RIGHT_BRACE"          => new StringParser("}"),             // ignored
			"LEFT_BRACKET"         => new StringParser("["),             // ignored
			"RIGHT_BRACKET"        => new StringParser("]"),             // ignored
			"COLON"                => new StringParser(":"),             // ignored
			"COMMA"                => new StringParser(","),             // ignored
			"DOUBLE_QUOTE"         => new StringParser("\""),            // ignored
			"SINGLE_QUOTE"         => new StringParser("'"),             // ignored
                             
			"NUMBER"               => new  RegexParser("#^-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][-+]?[0-9]+)?#", function($match)  { return (float) $match; }),
			"TRUE"                 => new StringParser("true"),
			"FALSE"                => new StringParser("false"),
			"NULL"                 => new StringParser("null"),
                             
			"UTF8_EXCEPT"          => new   Utf8Parser(array("\"", "\\")),
			"SQ_UTF8_EXCEPT"       => new   Utf8Parser(array("'", "\\")),
			"ESCAPED_QUOTE"        => new StringParser("\\\"",                   function($string) { return substr($string, 1, 1); }),
			"ESCAPED_SINGLE_QUOTE" => new StringParser("\\'",                    function($string) { return substr($string, 1, 1); }),
			"ESCAPED_BACKSLASH"    => new StringParser("\\\\",                   function($string) { return substr($string, 1, 1); }),
			"ESCAPED_SLASH"        => new StringParser("\\/",                    function($string) { return substr($string, 1, 1); }),
			"ESCAPED_B"            => new StringParser("\\b",                    function($string) { return "\b"; }),
			"ESCAPED_F"            => new StringParser("\\f",                    function($string) { return "\f"; }),
			"ESCAPED_N"            => new StringParser("\\n",                    function($string) { return "\n"; }),
			"ESCAPED_R"            => new StringParser("\\r",                    function($string) { return "\r"; }),
			"ESCAPED_T"            => new StringParser("\\t",                    function($string) { return "\t"; }),
			"ESCAPED_UTF8"         => new  RegexParser("#^\\\\u[0-9a-fA-F]{4}#", function($match)  { return Utf8Parser::getBytes(hexdec(substr($match, 2, 4))); })
		)
	);

	// if executing this file directly, run unit tests
	if(__FILE__ !== $_SERVER["PHP_SELF"]) {
		return;
	}

	// success case
	print("1\n");
	$start = microtime(true);
	$parseTree = $jsonPlusGrammar->parse(" { \"string\" : true, \"\\\"\" : false, \"\\u9874asdh\" : [ null, { }, -9488.44E+093 ] } ");
	print("Parsing completed in ".(microtime(true)-$start)." seconds\n");
	var_dump(true); // for successful parsing

	// print_r($parseTree);
	var_dump(count($parseTree) === 3);
	var_dump($parseTree["string"] === true);
	var_dump($parseTree["\""] === false);
	var_dump($parseTree["\xE9\xA1\xB4asdh"] === array(null, array(), -9.48844E+96));

	// failure modes
	print("2\n");
	foreach(
		array(
			  "{ \"string "        // incomplete string
			, "{ \"\\UAAAA\" "     // capital U on unicode char
			, "{ \"\\u000i\" "     // not enough hex digits on unicode char
			, "{ \"a\" : tru "     // incomplete "true"
			, "{ \"a\" :  +9 "     // leading +
			, "{ \"a\" :  9. "     // missing decimal digits
			, "{ \"a\" :  0a8.52 " // extraneous "a"
			, "{ \"a\" :  8E "     // missing exponent
			, "{ \"a\" :  08 "     // Two numbers side by side.
			, "[ \"a\" ,  8 ]"     // Not an object at the top level.
			, " \"a\" "            // Not an object at the top level.
		) as $string
	) {
		try {
			$jsonPlusGrammar->parse($string);
			var_dump(false);
		} catch(Exception $e) {
			var_dump(true);
		}
	}

	// single-quoted strings
	print("3\n");
	$parseTree = $jsonPlusGrammar->parse(" { 'string' : 'true' } ");
	var_dump(true); // for successful parsing
	var_dump(count($parseTree) === 1);
	var_dump($parseTree["string"] === "true");

	$parseTree = $jsonPlusGrammar->parse(" { 'str\"ing' : true, '\\'' : false, '\\u9874asdh' : [ null, { }, -9488.44E+093 ] } ");
	var_dump(true); // for successful parsing
	var_dump(count($parseTree) === 3);
	var_dump($parseTree["str\"ing"] === true);
	var_dump($parseTree["'"] === false);
	var_dump($parseTree["\xE9\xA1\xB4asdh"] === array(null, array(), -9.48844E+96));
	
	// single-quote errors
	print("4\n");
	foreach(
		array(
			  " { '\\\"' : 78 } "  // unnecessary escaping of a double-quote inside single-quotes
			, " { \"\\'\" : 78 } " // unnecessary escaping of a single-quote inside double-quotes
			, " { ''' : 78 } "     // bad character inside single-quoted string
		) as $string
	) {
		try {
			$jsonPlusGrammar->parse($string);
			var_dump(false);
		} catch(Exception $e) {
			var_dump(true);
		}
	}
?> 