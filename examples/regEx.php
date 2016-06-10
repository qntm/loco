<?php
namespace Ferno\Loco;

use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

# This code is in the public domain.
# http://qntm.org/loco

$regexGrammar = new Grammar(
	"<pattern>",
	array(
		// A Pattern is an alternation between several Concs, separated by pipes.
		"<pattern>" => new ConcParser(
			array("<conc>", "<pipeconclist>"),
			function($conc, $pipeconclist) {
				array_unshift($pipeconclist, $conc);
				return new Pattern($pipeconclist);
			}
		),
		"<pipeconclist>" => new GreedyStarParser(
			"<pipeconc>"
		),
		"<pipeconc>" => new ConcParser(
			array(
				new StringParser("|"),
				"<conc>"
			),
			function($pipe, $conc) { return $conc; }
		),

		// A Conc is a concatenation of several Mults.
		"<conc>" => new GreedyStarParser(
			"<mult>",
			function() { return new Conc(func_get_args()); }
		),

		// A Mult is a multiplicand (Charclass or sub-Pattern) followed by a multiplier.
		// A subpattern has to be put inside parentheses.
		"<mult>" => new ConcParser(
			array("<multiplicand>", "<multiplier>"),
			function($multiplicand, $multiplier) { return new Mult($multiplicand, $multiplier); }
		),
		"<multiplicand>" => new LazyAltParser(
			array("<subpattern>", "<charclass>")
		),
		"<subpattern>" => new ConcParser(
			array(new StringParser("("), "<pattern>", new StringParser(")")),
			function($left_parenthesis, $pattern, $right_parenthesis) { return $pattern; }
		),

		// A Multiplier has a lower bound and an upper bound. There are several short forms.
		// In the absence of a multiplier, {1,1} is assumed
		"<multiplier>" => new LazyAltParser(
			array(
				"<bracemultiplier>",
				new StringParser("?", function($string) { return new Multiplier(0, 1   ); }),
				new StringParser("*", function($string) { return new Multiplier(0, null); }),
				new StringParser("+", function($string) { return new Multiplier(1, null); }),
				new  EmptyParser(     function(       ) { return new Multiplier(1, 1   ); })
			)
		),

		"<bracemultiplier>" => new ConcParser(
			array(
				new StringParser("{"),
				"<multiplierinterior>",
				new StringParser("}")
			),
			function($left_brace, $multiplierinterior, $right_brace) { return $multiplierinterior; }
		),

		"<multiplierinterior>" => new LazyAltParser(
			array("<bothbounds>", "<unlimited>", "<onebound>")
		),
		"<bothbounds>" => new ConcParser(
			array("<integer>", "COMMA", "<integer>"),
			function($integer1, $comma, $integer2) { return new Multiplier($integer1, $integer2); }
		),
		"<unlimited>" => new ConcParser(
			array("<integer>", "COMMA"),
			function($integer, $comma) { return new Multiplier($integer, null); }
		),
		"<onebound>" => new ConcParser(
			array("<integer>"),
			function($integer) { return new Multiplier($integer, $integer); }
		),
		"COMMA" => new StringParser(","),
		"<integer>" => new RegexParser("#^(0|[1-9][0-9]*)#", function($match) { return (int)($match); }),

		// A Charclass is usually a single literal character.
		// It can also be a single character escaped with a backslash,
		// or a "true" charclass, which is a possibly-negated set of elements
		// listed inside a pair of brackets.
		"<charclass>" => new LazyAltParser(
			array(
				new RegexParser("#^[^|()\\[\\]?*+{}\\\\.]#", function($match) { return new Charclass($match); }),
				"<bracketednegatedcharclass>",
				"<bracketedcharclass>",
				new StringParser("\\|",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\(",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\)",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\[",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\]",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\?",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\*",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\+",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\{",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\}",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\\\", function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\.",  function($string) { return new Charclass(substr($string, 1, 1)); }),
				new StringParser("\\f",  function($string) { return new Charclass("\f"); }),
				new StringParser("\\n",  function($string) { return new Charclass("\n"); }),
				new StringParser("\\r",  function($string) { return new Charclass("\r"); }),
				new StringParser("\\t",  function($string) { return new Charclass("\t"); }),
				new StringParser("\\v",  function($string) { return new Charclass("\v"); }),
				new StringParser("\\w",  function($string) { return new Charclass("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz"); }),
				new StringParser("\\W",  function($string) { return new Charclass("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz", true); }),
				new StringParser("\\d",  function($string) { return new Charclass("0123456789"); }),
				new StringParser("\\D",  function($string) { return new Charclass("0123456789", true); }),
				new StringParser("\\s",  function($string) { return new Charclass(" \f\n\r\t\v"); }),
				new StringParser("\\S",  function($string) { return new Charclass(" \f\n\r\t\v", true); }),
				new StringParser(".",    function($string) { return new Charclass("", true); })
			)
		),

		"<bracketednegatedcharclass>" => new ConcParser(
			array("LEFT_BRACKET", "CARET", "<elemlist>", "RIGHT_BRACKET"),
			function($left_bracket, $elemlist, $right_bracket) { return new Charclass($elemlist, true); }
		),
		"<bracketedcharclass>" => new ConcParser(
			array("LEFT_BRACKET", "<elemlist>", "RIGHT_BRACKET"),
			function($left_bracket, $elemlist, $right_bracket) { return new Charclass($elemlist); }
		),
		"LEFT_BRACKET"  => new StringParser("["),
		"RIGHT_BRACKET" => new StringParser("]"),
		"CARET"         => new StringParser("^"),

		// A true charclass may be negated with a leading caret.
		"<elemlist>" => new GreedyStarParser(
			"<elem>",
			function() { return implode("", func_get_args()); }
		),

		// An element is either a single character or a character range.
		// A character range is represented with an optional hyphen
		"<elem>" => new LazyAltParser(
			array("<charrange>", "<classchar>")
		),

		"<charrange>" => new ConcParser(
			array("<classchar>", "HYPHEN", "<classchar>"),
			function($char1, $hyphen, $char2) {
				$char1 = ord($char1);
				$char2 = ord($char2);
				if($char2 < $char1) {
					throw new Exception("Disordered range");
				}
				$string = "";
				for($ord = $char1; $ord <= $char2; $ord++) {
					$string .= chr($ord);
				}
				return $string;
			}
		),
		"HYPHEN" => new StringParser("-"),

		// interior characters in character classes usually represent themselves,
		// but some are backslash-escaped
		"<classchar>" => new LazyAltParser(
			array(
				new RegexParser("#^[^\\\\\\[\\]\\^\\-]#"),
				new StringParser("\\\\", function($string) { return substr($string, 1, 1); }),
				new StringParser("\\[",  function($string) { return substr($string, 1, 1); }),
				new StringParser("\\]",  function($string) { return substr($string, 1, 1); }),
				new StringParser("\\^",  function($string) { return substr($string, 1, 1); }),
				new StringParser("\\-",  function($string) { return substr($string, 1, 1); }),
				new StringParser("\\f",  function($string) { return "\f"; }),
				new StringParser("\\n",  function($string) { return "\n"; }),
				new StringParser("\\r",  function($string) { return "\r"; }),
				new StringParser("\\t",  function($string) { return "\t"; }),
				new StringParser("\\v",  function($string) { return "\v"; })
			)
		)
	)
);

// Actual regex classes:

// A Charclass is a set of characters, possibly negated.
class Charclass {
	public $chars = array();
	public $negateMe = false;
	function __construct($chars, $negateMe = false) {
		if(!is_string($chars)) {
			throw new Exception("Not a string: ".var_export($chars, true));
		}
		if(!is_bool($negateMe)) {
			throw new Exception("Not a boolean: ".var_export($negateMe, true));
		}
		for($i = 0; $i < strlen($chars); $i++) {
			$char = $chars[$i];
			if(!in_array($char, $this->chars)) {
				$this->chars[] = $char;
			}
		}
		$this->negateMe = $negateMe;
	}

	// This is all a bit naive but it gives you the general picture
	public function __toString() {
		if(count($this->chars) === 0) {
			if($this->negateMe) {
				return ".";
			}
			throw new Exception("What");
		}

		if(count($this->chars) === 1 && $this->negateMe === false) {
			return $this->chars[0];
		}

		if($this->negateMe) {
			return "[^".implode("", $this->chars)."]";
		}

		return "[".implode("", $this->chars)."]";
	}
}

// A Multiplier consists of a non-negative integer lower bound and a non-negative
// integer upper bound greater than or equal to the lower bound.
// The upper bound can also be null (infinity)
class Multiplier {
	public $lower;
	public $upper;
	public function __construct($lower, $upper) {
		if(!is_int($lower)) {
			throw new Exception("Not an integer: ".var_export($lower, true));
		}
		if(!is_int($upper) && $upper !== null) {
			throw new Exception("Not an integer or null: ".var_export($upper, true));
		}
		if($upper !== null && !($lower <= $upper)) {
			throw new Exception("Upper: ".var_export($upper, true)." is less than lower: ".var_export($lower, true));
		}
		$this->lower = $lower;
		$this->upper = $upper;
	}
	public function __toString() {
		if($this->lower == 1 && $this->upper == 1) {
			return "";
		}
		if($this->lower == 0 && $this->upper == 1) {
			return "?";
		}
		if($this->lower == 0 && $this->upper === null) {
			return "*";
		}
		if($this->lower == 1 && $this->upper === null) {
			return "+";
		}
		if($this->upper === null) {
			return "{".$this->lower.",}";
		}
		if($this->lower == $this->upper) {
			return "{".$this->lower."}";
		}
		return "{".$this->lower.",".$this->upper."}";
	}
}

// Each Mult consists of a multiplicand (a Charclass or a Pattern) and a Multiplier
class Mult {
	public $multiplicand;
	public $multiplier;
	public function __construct($multiplicand, $multiplier) {
		if(!is_a($multiplicand, "Charclass") && !is_a($multiplicand, "Pattern")) {
			throw new Exception("Not a Charclass or Pattern: ".var_export($multiplicand, true));
		}
		if(!is_a($multiplier, "Multiplier")) {
			throw new Exception("Not a Multiplier: ".var_export($multiplier, true));
		}
		$this->multiplicand = $multiplicand;
		$this->multiplier = $multiplier;
	}
	public function __toString() {
		if(is_a($this->multiplicand, "Pattern")) {
			return "(".$this->multiplicand.")".$this->multiplier;
		}
		return $this->multiplicand.$this->multiplier;
	}
}

// Each Conc is a concatenation of several "Mults"
class Conc {
	public $mults;
	public function __construct($mults) {
		foreach($mults as $mult) {
			if(!is_a($mult, "Mult")) {
				throw new Exception("Not a Mult: ".var_export($mult, true));
			}
		}
		$this->mults = $mults;
	}
	public function __toString() {
		return implode("", $this->mults);
	}
}

// Each Pattern is an alternation between several "Concs"
// This is the top-level Pattern object returned by the lexer.
class Pattern {
	public $concs;
	public function __construct($concs) {
		foreach($concs as $conc) {
			if(!is_a($conc, "Conc")) {
				throw new Exception("Not a Conc: ".var_export($conc, true));
			}
		}
		$this->concs = $concs;
	}
	public function __toString() {
		return implode("|", $this->concs);
	}
}

// apologies for the relative lack of exhaustive unit tests

foreach(
	array(
		"a{2}",
		"a{2,}",
		"a{2,8}",
		"[$%\\^]{2,8}",
		"[ab]*",
		"([ab]*a)",
		"([ab]*a|[bc]*c)",
		"([ab]*a|[bc]*c)?",
		"([ab]*a|[bc]*c)?b*",
		"[a-zA-Z]",
		"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
		"[a]",
		"[abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]",
		"[|(){},?*+\\[\\]\\^.\\\\]",
		"[\\f\\n\\r\\t\\v\\-]",
		"\\|",
		"\\(\\)\\{\\},\\?\\*\\+\\[\\]^.-\\f\\n\\r\\t\\v\\w\\d\\s\\W\\D\\S\\\\",
		"abcdef",
		"19\\d\\d-\\d\\d-\\d\\d",
		"[$%\\^]{2,}",
		"[$%\\^]{2}",
		""
	) as $string
) {
	$pattern = $regexGrammar->parse($string);
	print($pattern."\n");
	var_dump(true);
}
