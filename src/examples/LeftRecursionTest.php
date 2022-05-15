<?php declare(strict_types=1);
# Left-recursion in Loco, demonstration.

# Left-recursive grammars cannot be parsed using a recursive descent approach.
# Loco detects left-recursion in a new grammar and raises an exception.
# How do we get around this?

namespace Ferno\Loco\examples;

use PHPUnit\Framework\TestCase;
use Ferno\Loco\RegexParser;
use Ferno\Loco\ConcParser;
use Ferno\Loco\StringParser;
use Ferno\Loco\Grammar;
use Ferno\Loco\LazyAltParser;
use Ferno\Loco\GrammarException;
use Ferno\Loco\GreedyStarParser;

final class LeftRecursionTest extends TestCase
{
    public function testLeftRecursion()
    {
        # N -> number
        $N = new RegexParser(
            "#^(0|[1-9][0-9]*)#",
            function ($match) {
                return (int) $match;
            }
        );

        # P -> "-" N
        $P = new ConcParser(
            array(new StringParser("-"), $N),
            function ($minus, $n) {
                return $n;
            }
        );

        # Naive left-recursive grammar looks like this and raises an exception
        # when instantiated.
        $threw = false;
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
        } catch (GrammarException $e) {
            # Left-recursive in S
            $threw = true;
        }
        $this->assertEquals($threw, true);

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
                    function ($n, $ps) {
                        # clever bit
                        return array_reduce($ps, function ($minuend, $subtrahend) {
                            # a left-associative operator.
                            # e.g. "5 - 4 - 3" means "(5 - 4) - 3 = -2", not "5 - (4 - 3) = 4".
                            return $minuend - $subtrahend;
                        }, $n);
                    }
                ),
                "P" => $P,
                "N" => $N
            )
        );
        $this->assertEquals($grammar->parse("5-4-3"), -2);
    }
}
