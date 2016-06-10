<?php
namespace Ferno\Loco;

use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Takes a string presented in Wirth syntax notation and turn it into a new
// Grammar object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Wirth_syntax_notation

# This code is in the public domain.
# http://qntm.org/locoparser

$wirthGrammar = new Grammar(
	"SYNTAX",
	array(
		"SYNTAX" => new GreedyStarParser("PRODUCTION"),
		"PRODUCTION" => new ConcParser(
			array(
				"whitespace",
				"IDENTIFIER",
				new StringParser("="),
				"whitespace",
				"EXPRESSION",
				new StringParser("."),
				"whitespace"
			),
			function($space1, $identifier, $equals, $space2, $expression, $dot, $space3) {
				return array("identifier" => $identifier, "expression" => $expression);
			}
		),
		"EXPRESSION" => new ConcParser(
			array(
				"TERM",
				new GreedyStarParser(
					new ConcParser(
						array(
							new StringParser("|"),
							"whitespace",
							"TERM"
						),
						function($pipe, $space, $term) {
							return $term;
						}
					)
				)
			),
			function($term, $terms) {
				array_unshift($terms, $term);
				return new LazyAltParser($terms);
			}
		),
		"TERM" => new GreedyMultiParser(
			"FACTOR",
			1,
			null,
			function() {
				return new ConcParser(func_get_args());
			}
		),
		"FACTOR" => new LazyAltParser(
			array(
				"IDENTIFIER",
				"LITERAL",
				new ConcParser(
					array(
						new StringParser("["),
						"whitespace",
						"EXPRESSION",
						new StringParser("]"),
						"whitespace"
					),
					function($bracket1, $space1, $expression, $bracket2, $space2) {
						return new GreedyMultiParser($expression, 0, 1);
					}
				),
				new ConcParser(
					array(
						new StringParser("("),
						"whitespace",
						"EXPRESSION",
						new StringParser(")"),
						"whitespace"
					),
					function($paren1, $space1, $expression, $paren2, $space2) {
						return $expression;
					}
				),
				new ConcParser(
					array(
						new StringParser("{"),
						"whitespace",
						"EXPRESSION",
						new StringParser("}"),
						"whitespace"
					),
					function($brace1, $space1, $expression, $brace2, $space2) {
						return new GreedyStarParser($expression);
					}
				)
			)
		),
		"IDENTIFIER" => new ConcParser(
			array(
				new GreedyMultiParser(
					"letter",
					1,
					null,
					function() {
						return implode("", func_get_args());
					}
				),
				"whitespace",
			),
			function($letters, $whitespace) {
				return $letters;
			}
		),
		"LITERAL" => new ConcParser(
			array(
				new StringParser("\""),
				new GreedyMultiParser(
					"character",
					1,
					null,
					function() {
						return implode("", func_get_args());
					}
				),
				new StringParser("\""),
				"whitespace"
			),
			function($quote1, $chars, $quote2, $whitespace) {
				return new StringParser($chars);
			}
		),
		"digit" => new RegexParser("#^[0-9]#"),
		"letter" => new RegexParser("#^[a-zA-Z]#"),
		"character" => new RegexParser(
			"#^([^\"]|\"\")#",
			function($match0) {
				if($match0 === "\"\"") {
					return "\"";
				}
				return $match0;
			}
		),
		"whitespace" => new RegexParser("#^[ \n\r\t]*#")
	),
	function($syntax) {
		$parsers = array();
		foreach($syntax as $production) {
			if(count($parsers) === 0) {
				$top = $production["identifier"];
			}
			$parsers[$production["identifier"]] = $production["expression"];
		}
		if(count($parsers) === 0) {
			throw new Exception("No rules.");
		}
		return new Grammar($top, $parsers);
	}
);

// if executing this file directly, run unit tests
if(__FILE__ !== $_SERVER["SCRIPT_FILENAME"]) {
	return;
}

// This is the syntax for Wirth syntax notation except it lacks whitespace
$string = "
	SYNTAX     = { PRODUCTION } .
	PRODUCTION = IDENTIFIER \"=\" EXPRESSION \".\" .
	EXPRESSION = TERM { \"|\" TERM } .
	TERM       = FACTOR { FACTOR } .
	FACTOR     = IDENTIFIER
						 | LITERAL
						 | \"[\" EXPRESSION \"]\"
						 | \"(\" EXPRESSION \")\"
						 | \"{\" EXPRESSION \"}\" .
	IDENTIFIER = letter { letter } .
	LITERAL    = \"\"\"\" character { character } \"\"\"\" .
	digit      = \"0\" | \"1\" | \"2\" | \"3\" | \"4\" | \"5\" | \"6\" | \"7\" | \"8\" | \"9\" .
	upper      = \"A\" | \"B\" | \"C\" | \"D\" | \"E\" | \"F\" | \"G\" | \"H\" | \"I\" | \"J\" 
			   | \"K\" | \"L\" | \"M\" | \"N\" | \"O\" | \"P\" | \"Q\" | \"R\" | \"S\" | \"T\" 
			   | \"U\" | \"V\" | \"W\" | \"X\" | \"Y\" | \"Z\" .
	lower      = \"a\" | \"b\" | \"c\" | \"d\" | \"e\" | \"f\" | \"g\" | \"h\" | \"i\" | \"j\" 
			   | \"k\" | \"l\" | \"m\" | \"n\" | \"o\" | \"p\" | \"q\" | \"r\" | \"s\" | \"t\" 
			   | \"u\" | \"v\" | \"w\" | \"x\" | \"y\" | \"z\" .
	letter     = upper | lower .
	character  = letter | digit | \"=\" | \".\" | \"\"\"\"\"\" .
";
$wirthGrammar->parse($string)->parse("SYNTAX={PRODUCTION}.");
var_dump(true); # for a successful parse
