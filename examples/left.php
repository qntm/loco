<?php
namespace Ferno\Loco;

require_once __DIR__ . '/../vendor/autoload.php';

# Left-recursion in Loco, demonstration.

# Left-recursive grammars cannot be parsed using a recursive descent approach.
# Loco detects left-recursion in a new grammar and raises an exception.
# How do we get around this?

# minus($minuend, $subtrahend) is a left-associative operator.
# e.g. "5 - 4 - 3" means "(5 - 4) - 3 = -2", not "5 - (4 - 3) = 4".
function minus($minuend, $subtrahend) {
	return $minuend - $subtrahend;
}

# N -> number
$N = new RegexParser(
	"#^(0|[1-9][0-9]*)#",
	function($match) { return (int) $match; }
);

# P -> "-" N
$P = new ConcParser(
	array(new StringParser("-"), $N),
	function($minus, $n) { return $n; }
);

# Naive left-recursive grammar looks like this and raises an exception
# when instantiated.
try {
	# S -> N
	# S -> S P
	$grammar = new Grammar(
		"S",
		array(
			"S" => new LazyAltParser(
				array(
					"N",
					new ConcParser(
						array("S", "P"),
						"minus"
					)
				)
			),
			"P" => $P,
			"N" => $N
		)
	);
	var_dump(false);
} catch (GrammarException $e) {
	# Left-recursive in S
	var_dump(true);
}

# Fix the grammar like so:
# S -> N P*
$grammar = new Grammar(
	"S",
	array(
		"S" => new ConcParser(
			array(
				$N,
				new GreedyStarParser("P")
			),
			function($n, $ps) {
				return array_reduce($ps, "minus", $n); # clever bit
			}
		),
		"P" => $P,
		"N" => $N
	)
);

var_dump($grammar->parse("5-4-3") === -2); # true
