<?php
namespace Ferno\Loco;

use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Takes a string presented in Extended Backus-Naur Form and turns it into a new Grammar
// object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_Form

// Can't handle exceptions, since these are not context-free
// Can't handle specials, which have no clear definition

# This code is in the public domain.
# http://qntm.org/locoparser

$ebnfGrammar = new Grammar(
	"<syntax>",
	array(
		"<syntax>" => new ConcParser(
			array("<space>", "<rules>"),
			function($space, $rules) {
				return $rules;
			}
		),

		"<rules>" => new GreedyStarParser("<rule>"),

		"<rule>" => new ConcParser(
			array("<bareword>", "<space>", new StringParser("="), "<space>", "<alt>", new StringParser(";"), "<space>"),
			function($bareword, $space1, $equals, $space2, $alt, $semicolon, $space3) {
				return array(
					"rule-name"  => $bareword,
					"expression" => $alt
				);
			}
		),

		"<alt>" => new ConcParser(
			array("<conc>", "<pipeconclist>"),
			function($conc, $pipeconclist) {
				array_unshift($pipeconclist, $conc);
				return new LazyAltParser($pipeconclist);
			}
		),

		"<pipeconclist>" => new GreedyStarParser("<pipeconc>"),

		"<pipeconc>" => new ConcParser(
			array(new StringParser("|"), "<space>", "<conc>"),
			function($pipe, $space, $conc) {
				return $conc;
			}
		),

		"<conc>" => new ConcParser(
			array("<term>", "<commatermlist>"),
			function($term, $commatermlist) {
				array_unshift($commatermlist, $term);

				// get array key numbers where multiparsers are located
				// in reverse order so that our splicing doesn't modify the array
				$multiparsers = array();
				foreach($commatermlist as $k => $internal) {
					if(is_a($internal, "GreedyMultiParser")) {
						array_unshift($multiparsers, $k);
					}
				}

				// We do something quite advanced here. The inner multiparsers are
				// spliced out into the list of arguments proper instead of forming an
				// internal sub-array of their own
				return new ConcParser(
					$commatermlist,
					function() use ($multiparsers) {
						$args = func_get_args();
						foreach($multiparsers as $k) {
							array_splice($args, $k, 1, $args[$k]);
						}
						return $args;
					}
				);
			}
		),

		"<commatermlist>" => new GreedyStarParser("<commaterm>"),

		"<commaterm>" => new ConcParser(
			array(new StringParser(","), "<space>", "<term>"),
			function($comma, $space, $term) {
				return $term;
			}
		),

		"<term>" => new LazyAltParser(
			array("<bareword>", "<sq>", "<dq>", "<group>", "<repetition>", "<optional>")
		),

		"<bareword>" => new ConcParser(
			array(
				new RegexParser(
					"#^([a-z][a-z ]*[a-z]|[a-z])#",
					function($match0) {
						return $match0;
					}
				),
				"<space>"
			),
			function($bareword, $space) {
				return $bareword;
			}
		),

		"<sq>" => new ConcParser(
			array(
				new RegexParser(
					"#^'([^']*)'#",
					function($match0, $match1) {
						if($match1 === "") {
							return new EmptyParser();
						}
						return new StringParser($match1);
					}
				),
				"<space>"
			),
			function($string, $space) {
				return $string;
			}
		),

		"<dq>" => new ConcParser(
			array(
				new RegexParser(
					'#^"([^"]*)"#',
					function($match0, $match1) {
						if($match1 === "") {
							return new EmptyParser();
						}
						return new StringParser($match1);
					}
				),
				"<space>"
			),
			function($string, $space) {
				return $string;
			}
		),

		"<group>" => new ConcParser(
			array(
				new StringParser("("),
				"<space>",
				"<alt>",
				new StringParser(")"),
				"<space>"
			),
			function($left_paren, $space1, $alt, $right_paren, $space2) {
				return $alt;
			}
		),

		"<repetition>" => new ConcParser(
			array(
				new StringParser("{"),
				"<space>",
				"<alt>",
				new StringParser("}"),
				"<space>"
			),
			function($left_brace, $space1, $alt, $right_brace, $space2) {
				return new GreedyStarParser($alt);
			}
		),

		"<optional>" => new ConcParser(
			array(
				new StringParser("["),
				"<space>",
				"<alt>",
				new StringParser("]"),
				"<space>"
			),
			function($left_bracket, $space1, $alt, $right_bracket, $space2) {
				return new GreedyMultiParser($alt, 0, 1);
			}
		),

		"<space>" => new GreedyStarParser("<whitespace/comment>"),

		"<whitespace/comment>" => new LazyAltParser(
			array("<whitespace>", "<comment>")
		),

		"<whitespace>" => new RegexParser("#^[ \t\r\n]+#"),
		"<comment>" => new RegexParser("#^(\(\* [^*]* \*\)|\(\* \*\)|\(\*\*\))#")
	),
	function($syntax) {
		$parsers = array();
		foreach($syntax as $rule) {
			if(count($parsers) === 0) {
				$top = $rule["rule-name"];
			}
			$parsers[$rule["rule-name"]] = $rule["expression"];
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

$string = "a = 'PROGRAM' ;";
$ebnfGrammar->parse($string)->parse("PROGRAM");
var_dump(true);

// Should raise a ParseFailureException before trying to instantiate a Grammar
// with no rules and raising a GrammarException
$string = "a = 'PROGRAM ;";
try { $ebnfGrammar->parse($string); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }

// Full rule set
$string = "
	(* a simple program syntax in EBNF - Wikipedia *)
	program = 'PROGRAM' , white space , identifier , white space ,
						 'BEGIN' , white space ,
						 { assignment , \";\" , white space } ,
						 'END.' ;
	identifier = alphabetic character , { alphabetic character | digit } ;
	number = [ \"-\" ] , digit , { digit } ;
	string = '\"' , { all characters } , '\"' ;
	assignment = identifier , \":=\" , ( number | identifier | string ) ;
	alphabetic character = \"A\" | \"B\" | \"C\" | \"D\" | \"E\" | \"F\" | \"G\"
											 | \"H\" | \"I\" | \"J\" | \"K\" | \"L\" | \"M\" | \"N\"
											 | \"O\" | \"P\" | \"Q\" | \"R\" | \"S\" | \"T\" | \"U\"
											 | \"V\" | \"W\" | \"X\" | \"Y\" | \"Z\" ;
	digit = \"0\" | \"1\" | \"2\" | \"3\" | \"4\" | \"5\" | \"6\" | \"7\" | \"8\" | \"9\" ;
	white space = ( \" \" | \"\n\" ) , { \" \" | \"\n\" } ;
	all characters = \"H\" | \"e\" | \"l\" | \"o\" | \" \" | \"w\" | \"r\" | \"d\" | \"!\" ;
";
$pascalGrammar = $ebnfGrammar->parse($string);
var_dump(true);

$string =
	 "PROGRAM DEMO1\n"
	."BEGIN\n"
	."  A0:=3;\n"
	."  B:=45;\n"
	."  H:=-100023;\n"
	."  C:=A;\n"
	."  D123:=B34A;\n"
	."  BABOON:=GIRAFFE;\n"
	."  TEXT:=\"Hello world!\";\n"
	."END."
;
$pascalGrammar->parse($string);
var_dump(true);
