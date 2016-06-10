<?php
namespace Ferno\Loco;

use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Takes a string presented in Backus-Naur Form and turns it into a new Grammar
// object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Backus%E2%80%93Naur_Form

# This code is in the public domain.
# http://qntm.org/locoparser

$bnfGrammar = new Grammar(
	"<syntax>",
	array(
		"<syntax>" => new ConcParser(
			array(
				"<rules>",
				"OPT-WHITESPACE"
			),
			function($rules, $whitespace) { return $rules; }
		),

		"<rules>" => new GreedyMultiParser(
			"<ruleoremptyline>",
			1,
			null,
			function() {
				$rules = array();
				foreach(func_get_args() as $rule) {

					// blank line
					if($rule === null) {
						continue;
					}

					$rules[] = $rule;
				}
				return $rules;
			}
		),

		"<ruleoremptyline>" => new LazyAltParser(
			array("<rule>", "<emptyline>")
		),

		"<emptyline>" => new ConcParser(
			array("OPT-WHITESPACE", "EOL"),
			function($whitespace, $eol) {
				return null;
			}
		),

		"<rule>" => new ConcParser(
			array(
				"OPT-WHITESPACE",
				"RULE-NAME",
				"OPT-WHITESPACE",
				new StringParser("::="),
				"OPT-WHITESPACE",
				"<expression>",
				"EOL"
			),
			function(
				$whitespace1,
				$rule_name,
				$whitespace2,
				$equals,
				$whitespace3,
				$expression,
				$eol
			) {
				return array(
					"rule-name"  => $rule_name,
					"expression" => $expression
				);
			}
		),

		"<expression>" => new ConcParser(
			array(
				"<list>",
				"<pipelists>"
			),
			function($list, $pipelists) {
				array_unshift($pipelists, $list);
				return new LazyAltParser($pipelists);
			}
		),

		"<pipelists>" => new GreedyStarParser("<pipelist>"),

		"<pipelist>" => new ConcParser(
			array(
				new StringParser("|"),
				"OPT-WHITESPACE",
				"<list>"
			),
			function($pipe, $whitespace, $list) {
				return $list;
			}
		),

		"<list>" => new GreedyMultiParser(
			"<term>",
			1,
			null,
			function() {
				return new ConcParser(func_get_args());
			}
		),

		"<term>" => new ConcParser(
			array("TERM", "OPT-WHITESPACE"),
			function($term, $whitespace) {
				return $term;
			}
		),

		"TERM" => new LazyAltParser(
			array(
				"LITERAL",
				"RULE-NAME"
			)
		),

		"LITERAL" => new LazyAltParser(
			array(
				new RegexParser('#^"([^"]*)"#', function($match0, $match1) { return $match1; }),
				new RegexParser("#^'([^']*)'#", function($match0, $match1) { return $match1; })
			),
			function($text) {
				if($text == "") {
					return new EmptyParser(function() { return ""; });
				}
				return new StringParser($text);
			}
		),

		"RULE-NAME" => new RegexParser("#^<[A-Za-z\\-]*>#"),

		"OPT-WHITESPACE" => new RegexParser("#^[\t ]*#"),

		"EOL" => new LazyAltParser(
			array(
				new StringParser("\r"),
				new StringParser("\n")
			)
		)
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

// Full rule set
$string = "
	<postal-address> ::= <name-part> <street-address> <zip-part>
	<name-part>      ::= <personal-part> <name-part> | <personal-part> <last-name> <opt-jr-part> <EOL>
	<personal-part>  ::= <initial> \".\" | <first-name>
	<street-address> ::= <house-num> <street-name> <opt-apt-num> <EOL>
	<zip-part>       ::= <town-name> \",\" <state-code> <ZIP-code> <EOL>
	<opt-jr-part>    ::= \"Sr.\" | \"Jr.\" | <roman-numeral> | \"\"

	<last-name>     ::= 'MacLaurin '
	<EOL>           ::= '\n'
	<initial>       ::= 'b'
	<first-name>    ::= 'Steve '
	<house-num>     ::= '173 '
	<street-name>   ::= 'Acacia Avenue '
	<opt-apt-num>   ::= '7A'
	<town-name>     ::= 'Stevenage'
	<state-code>    ::= ' KY '
	<ZIP-code>      ::= '33445'
	<roman-numeral> ::= 'g'
";

$start = microtime(true);
$grammar2 = $bnfGrammar->parse($string);
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");

$start = microtime(true);
$grammar2->parse("Steve MacLaurin \n173 Acacia Avenue 7A\nStevenage, KY 33445\n");
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");

$string = "
	<syntax>         ::= <rule> | <rule> <syntax>
	<rule>           ::= <opt-whitespace> \"<\" <rule-name> \">\" <opt-whitespace> \"::=\" <opt-whitespace> <expression> <line-end>
	<opt-whitespace> ::= \" \" <opt-whitespace> | \"\"
	<expression>     ::= <list> | <list> \"|\" <expression>
	<line-end>       ::= <opt-whitespace> <EOL> <line-end> | <opt-whitespace> <EOL>
	<list>           ::= <term> | <term> <opt-whitespace> <list>
	<term>           ::= <literal> | \"<\" <rule-name> \">\"
	<literal>        ::= '\"' <text> '\"' | \"'\" <text> \"'\"
	
	<rule-name>      ::= 'a'
	<EOL>            ::= '\n'
	<text>           ::= 'b'
";

$start = microtime(true);
$grammar3 = $bnfGrammar->parse($string);
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");

$start = microtime(true);
$grammar3->parse(" <a> ::= 'b' \n");
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");

// Should raise a ParseFailureException before trying to instantiate a Grammar
$string = " <incomplete ::=";
try { $bnfGrammar->parse($string); var_dump(false); } catch(ParseFailureException $e) { }
