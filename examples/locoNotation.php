<?php
namespace Ferno\Loco;

use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Takes a string presented in Loco Backus-Naur Form and turns it into a
// new Grammar object capable of recognising the language described by that string.

# This code is in the public domain.
# http://qntm.org/locoparser

$locoGrammar = new Grammar(
	"<grammar>",
	array(
		"<grammar>" => new ConcParser(
			array("<whitespace>", "<rules>"),
			function($whitespace, $rules) {
				return $rules;
			}
		),

		"<rules>" => new GreedyStarParser(
			"<ruleorblankline>",
			function() {
				$rules = array();
				foreach(func_get_args() as $ruleorblankline) {
					if($ruleorblankline === null) {
						continue;
					}
					$rules[] = $ruleorblankline;
				}
				return $rules;
			}
		),

		"<ruleorblankline>" => new LazyAltParser(
			array("<rule>", "<blankline>")
		),

		"<blankline>" => new ConcParser(
			array(
				new RegexParser("#^\r?\n#"),
				"<whitespace>"
			),
			function() {
				return null;
			}
		),

		"<rule>" => new ConcParser(
			array(
				"<bareword>",
				"<whitespace>",
				new StringParser("::="),
				"<whitespace>",
				"<lazyaltparser>"
			),
			function($bareword, $whitespace1, $equals, $whitespace2, $lazyaltparser) {
				return array(
					"name" => $bareword,
					"lazyaltparser" => $lazyaltparser
				);
			}
		),
		
		"<lazyaltparser>" => new ConcParser(
			array("<concparser>", "<pipeconcparserlist>"),
			function($concparser, $pipeconcparserlist) {
				array_unshift($pipeconcparserlist, $concparser);

				// make a basic lazyaltparser which returns whatever.
				// Since the LazyAltParser always contains 0 or more ConcParsers,
				// the value of $result is always an array
				return new LazyAltParser(
					$pipeconcparserlist
				);
			}
		),

		"<pipeconcparserlist>" => new GreedyStarParser("<pipeconcparser>"),

		"<pipeconcparser>" => new ConcParser(
			array(
				new StringParser("|"),
				"<whitespace>",
				"<concparser>"
			),
			function($pipe, $whitespace, $concparser) { return $concparser; }
		),

		"<concparser>" => new GreedyStarParser(
			"<bnfmultiplication>",
			function() {
				// get array key numbers where multiparsers are located
				// in reverse order so that our splicing doesn't modify the array
				$multiparsers = array();
				foreach(func_get_args() as $k => $internal) {
					if(is_a($internal, "GreedyMultiParser")) {
						array_unshift($multiparsers, $k);
					}
				}

				// We do something quite advanced here. The inner multiparsers are
				// spliced out into the list of arguments proper instead of forming an
				// internal sub-array of their own
				return new ConcParser(
					func_get_args(),
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
	
		"<bnfmultiplication>" => new ConcParser(
			array("<bnfmultiplicand>", "<whitespace>", "<bnfmultiplier>", "<whitespace>"),
			function($bnfmultiplicand, $whitespace1, $bnfmultiplier, $whitespace2) {

				if(is_array($bnfmultiplier)) {
					return new GreedyMultiParser(
						$bnfmultiplicand,
						$bnfmultiplier["lower"],
						$bnfmultiplier["upper"]
					);
				}
				
				// otherwise assume multiplier = 1
				return $bnfmultiplicand;
			}
		),

		"<bnfmultiplicand>" => new LazyAltParser(
			array(
				 "<bareword>"        // i.e. the name of another rule elsewhere in the grammar
				, "<dqstringparser>" // double-quoted string e.g. "fred"
				, "<sqstringparser>" // single-quoted string e.g. 'velma'
				, "<regexparser>"    // slash-quoted regex e.g. /[a-zA-Z_][a-zA-Z_0-9]*/
				, "<utf8except>"     // e.g. [^abcdef]
				, "<utf8parser>"     // i.e. a single full stop, .
				, "<subparser>"      // another expression inside parentheses e.g. ( firstname lastname )
			)
		),

		"<bnfmultiplier>" => new LazyAltParser(
			array("<asterisk>", "<plus>", "<questionmark>", "<emptymultiplier>")
		),

		"<asterisk>" => new StringParser(
			"*",
			function() { return array("lower" => 0, "upper" => null); }
		),
		
		"<plus>" => new StringParser(
			"+",
			function() { return array("lower" => 1, "upper" => null); }
		),
		
		"<questionmark>" => new StringParser(
			"?",
			function() { return array("lower" => 0, "upper" => 1); }
		),
		
		"<emptymultiplier>" => new EmptyParser(),

		// return a basic parser which recognises this string
		"<dqstringparser>" => new ConcParser(
			array(
				new StringParser("\""),
				"<dqstring>",
				new StringParser("\"")
			),
			function($quote1, $string, $quote2) {
				if($string === "") {
					return new EmptyParser();
				}
				return new StringParser($string);
			}
		),

		"<sqstringparser>" => new ConcParser(
			array(
				new StringParser("'"),
				"<sqstring>",
				new StringParser("'")
			),
			function($apostrophe1, $string, $apostrophe2) {
				if($string === "") {
					return new EmptyParser();
				}
				return new StringParser($string);
			}
		),

		"<dqstring>" => new GreedyStarParser(
			"<dqstrchar>",
			function() { return implode("", func_get_args()); }
		),
		
		"<sqstring>" => new GreedyStarParser(
			"<sqstrchar>",
			function() { return implode("", func_get_args()); }
		),

		"<dqstrchar>" => new LazyAltParser(
			array(
				new Utf8Parser(array("\\", "\"")),
				new StringParser("\\\\", function($string) { return "\\"; }),
				new StringParser('\\"', function($string) { return '"'; })
			)
		),
		
		"<sqstrchar>" => new LazyAltParser(
			array(
				new Utf8Parser(array("\\", "'")),
				new StringParser("\\\\", function($string) { return "\\"; }),
				new StringParser("\\'" , function($string) { return "'"; })
			)
		),

		// return a basic parser matching this regex
		"<regexparser>" => new ConcParser(
			array(
				new StringParser("/"),
				"<regex>",
				new StringParser("/")
			),
			function($slash1, $regex, $slash2) {
				if($regex === "") {
					return new EmptyParser();
				}

				// Add the anchor and the brackets to make sure it anchors in the
				// correct location
				$regex = "/^(".$regex.")/";
				// print("Actual regex is: ".$regex."\n");
				return new RegexParser($regex);
			}
		),
		
		"<regex>" => new GreedyStarParser(
			"<rechar>",
			function() { return implode("", func_get_args()); }
		),
		
		// Regular expression contains: Any single character that is not a slash or backslash...
		// OR any single character escaped by a backslash. Return as literal.
		"<rechar>" => new LazyAltParser(
			array(
				new Utf8Parser(array("\\", "/")),
				new ConcParser(
					array(
						new StringParser("\\"),
						new Utf8Parser()
					),
					function($backslash, $char) {
						return $backslash.$char;
					}
				)
			)
		),

		"<utf8except>" => new ConcParser(
			array(
				new StringParser("[^"),
				"<exceptions>",
				new StringParser("]")
			),
			function($left_bracket_caret, $exceptions, $right_bracket) {
				return new Utf8Parser($exceptions);
			}
		),
		
		"<exceptions>" => new GreedyStarParser("<exceptionchar>"),
		
		"<exceptionchar>" => new LazyAltParser(
			array(
				new Utf8Parser(array("\\", "]")),
				new StringParser("\\\\", function($string) { return "\\"; }),
				new StringParser("\\]" , function($string) { return "]"; })
			)
		),

		"<utf8parser>" => new StringParser(
			".",
			function() {
				return new Utf8Parser(array());
			}
		),
		
		"<subparser>" => new ConcParser(
			array(
				new StringParser("("),
				"<whitespace>",
				"<lazyaltparser>",
				new StringParser(")")
			),
			function($left_parenthesis, $whitespace1, $lazyaltparser, $right_parenthesis) {
				return $lazyaltparser;
			}
		),
		
		"<whitespace>"         => new  RegexParser("#^[ \t]*#"),
		"<bareword>"           => new  RegexParser("#^[a-zA-Z_][a-zA-Z0-9_]*#")
	),
	function($rules) {
		$parsers = array();
		foreach($rules as $rule) {
			if(count($parsers) === 0) {
				$top = $rule["name"];
			}
			$parsers[$rule["name"]] = $rule["lazyaltparser"];
		}
		return new Grammar($top, $parsers);
	}
);

// if executing this file directly, run unit tests
if(__FILE__ !== $_SERVER["SCRIPT_FILENAME"]) {
	return;
}

// parentheses inside your BNF *always* force an array to exist in the output
// *, +, ? and {m,n} are not disguised parentheses; they expand into the main expression
// in the absence of a function to call, an array is is built instead

print("0A\n");

// basic
// array("a") or new S("a")
$grammar2 = $locoGrammar->parse(" S ::= 'a' ");
var_dump($grammar2->parse("a") === array("a"));

// concatenation
// array("a", "b") or new S("a", "b")
$grammar2 = $locoGrammar->parse(" S ::= 'a' 'b' ");
var_dump($grammar2->parse("ab") === array("a", "b"));

// alternation
// array("a") or array("b") or new S("a") or new S("b")
$grammar2 = $locoGrammar->parse(" S ::= 'a' | 'b' ");
var_dump($grammar2->parse("a") === array("a"));
var_dump($grammar2->parse("b") === array("b"));

// alternation 2
// array("a") or array("b", "c") or new S("a") or new S("b", "c")
$grammar2 = $locoGrammar->parse(" S ::= 'a' | 'b' 'c' ");
var_dump($grammar2->parse("a")  === array("a"));
var_dump($grammar2->parse("bc") === array("b", "c"));

// subparsers
// array(array("a") or new S(array("a"))
$grammar2 = $locoGrammar->parse(" S ::= ('a') ");
var_dump($grammar2->parse("a") === array(array("a")));

print("0B\n");

// new S(new A("a"))
$grammar1 = $locoGrammar->parse(" S ::= A \n A ::= 'a' ");
var_dump($grammar1->parse("a") === array(array("a")));

// new S(new A("a", "b"))
$grammar1 = $locoGrammar->parse(" S ::= A \n A ::= 'a' 'b' ");
var_dump($grammar1->parse("ab") === array(array("a", "b")));

// Question mark multiplier
// new S("a") or new S()
$grammar1 = $locoGrammar->parse(" S ::= 'a'? ");
$grammar2 = $locoGrammar->parse(" S ::= 'a' | ");
// new S(new AQ("a")) or new S(new AQ())
$grammar3 = $locoGrammar->parse(" S ::= ( 'a' | ) ");
$grammar4 = $locoGrammar->parse(" S ::= AQ \n AQ ::= 'a' | ");
var_dump($grammar1->parse("a") === array("a"));
var_dump($grammar2->parse("a") === array("a"));
var_dump($grammar3->parse("a") === array(array("a")));
var_dump($grammar4->parse("a") === array(array("a")));
var_dump($grammar1->parse("") === array());
var_dump($grammar2->parse("") === array());
var_dump($grammar3->parse("") === array(array()));
var_dump($grammar4->parse("") === array(array()));

print("0D\n");

// Star parser
// new S("a", "a", ...)
// array("a", "a", ...)
$grammar1 = $locoGrammar->parse(" S ::= 'a'* ");
$grammar4 = $locoGrammar->parse(" S ::= 'a' 'a' 'a' | 'a' 'a' | 'a' | ");
// new S(array("a", "a", ...))
// new S(new AStar("a", "a", ...))
$grammar2 = $locoGrammar->parse(" S ::= ( 'a' 'a' 'a' | 'a' 'a' | 'a' | ) ");
$grammar3 = $locoGrammar->parse(" S ::= AStar \n AStar ::= 'a' 'a' 'a' | 'a' 'a' | 'a' | ");
var_dump($grammar1->parse("aaa") === array("a", "a", "a"));
var_dump($grammar4->parse("aaa") === array("a", "a", "a"));
var_dump($grammar2->parse("aaa") === array(array("a", "a", "a")));
var_dump($grammar3->parse("aaa") === array(array("a", "a", "a")));
var_dump($grammar1->parse("aa") === array("a", "a"));
var_dump($grammar4->parse("aa") === array("a", "a"));
var_dump($grammar2->parse("aa") === array(array("a", "a")));
var_dump($grammar3->parse("aa") === array(array("a", "a")));
var_dump($grammar1->parse("a") === array("a"));
var_dump($grammar4->parse("a") === array("a"));
var_dump($grammar2->parse("a") === array(array("a")));
var_dump($grammar3->parse("a") === array(array("a")));
var_dump($grammar1->parse("") === array());
var_dump($grammar4->parse("") === array());
var_dump($grammar2->parse("") === array(array()));
var_dump($grammar3->parse("") === array(array()));

print("0E\n");

// Plus parser
// new S(array("a", "a", ...))
// new S(new APlus("a", "a", ...))
$grammar1 = $locoGrammar->parse(" S ::= 'a'+ ");
$grammar4 = $locoGrammar->parse(" S ::= 'a' 'a' 'a' | 'a' 'a' | 'a' ");
$grammar2 = $locoGrammar->parse(" S ::= ( 'a' 'a' 'a' | 'a' 'a' | 'a' ) ");
$grammar3 = $locoGrammar->parse(" S ::= APlus \n APlus ::= 'a' 'a' 'a' | 'a' 'a' | 'a' ");
var_dump($grammar1->parse("aaa") === array("a", "a", "a"));
var_dump($grammar4->parse("aaa") === array("a", "a", "a"));
var_dump($grammar2->parse("aaa") === array(array("a", "a", "a")));
var_dump($grammar3->parse("aaa") === array(array("a", "a", "a")));
var_dump($grammar1->parse("aa") === array("a", "a"));
var_dump($grammar4->parse("aa") === array("a", "a"));
var_dump($grammar2->parse("aa") === array(array("a", "a")));
var_dump($grammar3->parse("aa") === array(array("a", "a")));
var_dump($grammar1->parse("a") === array("a"));
var_dump($grammar4->parse("a") === array("a"));
var_dump($grammar2->parse("a") === array(array("a")));
var_dump($grammar3->parse("a") === array(array("a")));
try { $grammar1->parse(""); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }
try { $grammar4->parse(""); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }
try { $grammar2->parse(""); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }
try { $grammar3->parse(""); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }

// Regexes and Leaning Toothpick Syndrome
print("2Aa\n");
$grammar1 = $locoGrammar->parse(" S ::= /(ab)*/ ");
var_dump($grammar1->parse("ababab") === array("ababab"));
$grammar = $locoGrammar->parse(" number ::= /a\\.b/ ");
var_dump($grammar->parse("a.b") === array("a.b"));
try { $grammar1->parse("aXb"); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }

print("2Ab\n");

// parse a literal slash
$grammar1 = $locoGrammar->parse(" S ::= /\\// ");
var_dump($grammar1->parse("/") === array("/"));

// parse a literal backslash
$grammar1 = $locoGrammar->parse(" S ::= /\\\\/ ");
var_dump($grammar1->parse("\\") === array("\\"));

// UTF-8 dot (equivalent to [^], if you think about it!)
print("2Ba\n");
$grammar1 = $locoGrammar->parse(" S ::= . ");
try { $grammar1->parse(""); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); }
var_dump($grammar1->parse("\x41"            ) === array("\x41"            )); # 1-byte character "A"
var_dump($grammar1->parse("\xC2\xAF"        ) === array("\xC2\xAF"        )); # 2-byte character "�"
var_dump($grammar1->parse("\xE2\x99\xA5"    ) === array("\xE2\x99\xA5"    )); # 3-byte character "?"
var_dump($grammar1->parse("\xF1\x8B\x81\x82") === array("\xF1\x8B\x81\x82")); # 4-byte character "??"
var_dump($grammar1->parse("\xEF\xBB\xBF"    ) === array("\xEF\xBB\xBF"    )); # "byte order mark" 11101111 10111011 10111111 (U+FEFF)
var_dump($grammar1->parse("\xF0\x90\x80\x80") === array("\xF0\x90\x80\x80")); # 4-byte character
var_dump($grammar1->parse("\xF0\xA0\x80\x80") === array("\xF0\xA0\x80\x80")); # 4-byte character
try { $grammar1->parse("\xF4\x90\x80\x80"); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # code point U+110000, out of range (max is U+10FFFF)
try { $grammar1->parse("\xC0\xA6"        ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # overlong encoding (code point is U+26; should be 1 byte, "\x26")
try { $grammar1->parse("\xC3\xFF"        ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # illegal continuation byte
try { $grammar1->parse("\xFF"            ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # illegal leading byte
try { $grammar1->parse("\xC2"            ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # mid-character termination
try { $grammar1->parse("\x00"            ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # null
try { $grammar1->parse("\xED\xA0\x80"    ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # 55296d
try { $grammar1->parse("\xED\xBF\xBF"    ); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } # 57343d

// UTF-8 long form with exceptions
print("2Bb\n");
$grammar1 = $locoGrammar->parse(" S ::= [^& <>\\]] ");
var_dump($grammar1->parse("A") === array("A"));
var_dump($grammar1->parse("^") === array("^"));
var_dump($grammar1->parse("\\") === array("\\"));
try { $grammar1->parse("&"); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } 
try { $grammar1->parse(" "); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } 
try { $grammar1->parse("<"); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } 
try { $grammar1->parse(">"); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } 
try { $grammar1->parse("]"); var_dump(false); } catch(ParseFailureException $e) { var_dump(true); } 

// two rules
print("3A\n");
$start = microtime(true);
$grammar2 = $locoGrammar->parse(" 
	unicode ::= 'a' '' b | 'grammar' '\\'' '\\\\' \"\\\"\"
	b::=
");
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");

$start = microtime(true);
$result = $grammar2->parse("grammar'\\\"");
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");
var_dump($result === array("grammar", "'", "\\", "\""));

// bracket matching
print("5\n");

$bracketMatchGrammar = $locoGrammar->parse("
	S          ::= expression *
	expression ::= '<' S '>'
");

foreach(
	array(
		"",
		"<>",
		"<><>",
		"<<>>",
		"<<<><>>><><<>>"
	) as $string
) {
	$bracketMatchGrammar->parse($string);
	var_dump(true);
}

foreach(
	array(
		" ",
		"<",
		">",
		"<><",
		"<<>",
		"<<<><>>><><><<<<>>>>>"
	) as $string
) {
	try {
		$bracketMatchGrammar->parse($string);
		var_dump(false);
	} catch(ParseFailureException $e) {
		var_dump(true);
	}
}

print("11\n");

// Full rules for recognising JSON
$jsonGrammar = $locoGrammar->parse("
	topobject      ::= whitespace object
	object         ::= '{' whitespace objectcontent '}' whitespace
	objectcontent  ::= fullobject | ()
	fullobject     ::= keyvalue (comma keyvalue)*
	keyvalue       ::= string ':' whitespace value
	array          ::= '[' whitespace arraycontent ']' whitespace
	arraycontent   ::= fullarray | ()
	fullarray      ::= value (comma value)*
	value          ::= string | number | object | array | true | false | null
	string         ::= '\"' stringcontent '\"' whitespace
	stringcontent  ::= char *
	char           ::= [^\"\\\\] | '\\\\' escapesequence
	escapesequence ::= '\"' | '\\\\' | '/' | 'b' | 'f' | 'n' | 'r' | 't' | /u[0-9a-fA-F]{4}/
	number         ::= /-?(0|[1-9][0-9]*)(\\.[0-9]+)?([eE][-+]?[0-9]+)?/ whitespace
	true           ::= 'true' whitespace
	false          ::= 'false' whitespace
	null           ::= 'null' whitespace
	comma          ::= ',' whitespace
	whitespace     ::= /[ \n\r\t]*/
");

$start = microtime(true);
$result = $jsonGrammar->parse(" { \"string\" : true, \"\\\"\" : false, \"\\u9874asdh\" : [ null, { }, -9488.44E+093 ] } ");
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");
var_dump(true); // for successful parsing

// failure modes
print("12\n");
foreach(
	array(
		"{ \"string ",        // incomplete string
		"{ \"\\UAAAA\" ",     // capital U on unicode char
		"{ \"\\u000i\" ",     // not enough hex digits on unicode char
		"{ \"a\" : tru ",     // incomplete "true"
		"{ \"a\" :  +9 ",     // leading +
		"{ \"a\" :  9. ",     // missing decimal digits
		"{ \"a\" :  0a8.52 ", // extraneous "a"
		"{ \"a\" :  8E ",     // missing exponent
		"{ \"a\" :  08 "      // Two numbers side by side.
	) as $string
) {
	try {
		$jsonGrammar->parse($string);
		var_dump(false);
	} catch(Exception $e) {
		var_dump(true);
	}
}

// Comments for qntm.org
print("30\n");

$simpleCommentGrammar = $locoGrammar->parse("
	comment    ::= whitespace block*
	block      ::= h5 whitespace | p whitespace
	p          ::= '<p'      whitespace '>' text '</p'      whitespace '>'
	h5         ::= '<h5'     whitespace '>' text '</h5'     whitespace '>'
	strong     ::= '<strong' whitespace '>' text '</strong' whitespace '>'
	em         ::= '<em'     whitespace '>' text '</em'     whitespace '>'
	br         ::= '<br'     whitespace '/>'
	text       ::= atom*
	atom       ::= [^<>&] | '&' entity ';' | strong | em | br
	entity     ::= 'gt' | 'lt' | 'amp'
	whitespace ::= /[ \n\r\t]*/
");

$start = microtime(true);
$string = $simpleCommentGrammar->parse("
	<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>
	\r\n\t
	<p  >&lt;</p  >
");
print("Parsing completed in ".(microtime(true)-$start)." seconds\n");

foreach(
	array(
		"<h5 style=\"\">", // rogue "style" attribute
		"&",               // unescaped AMPERSAND
		"<",               // unescaped LESS_THAN
		"salkhsfg>",       // unescaped GREATER_THAN
		"</p",             // incomplete CLOSE_P
		"<br"              // incomplete FULL_BR
	) as $string
) {
	try {
		$simpleCommentGrammar->parse($string);
		var_dump(false);
	} catch(Exception $e) {
		var_dump(true);
	}
}
