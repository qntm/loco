<?php
# Left-recursion in Loco, demonstration.

# Left-recursive grammars cannot be parsed using a recursive descent approach.
# Loco detects left-recursion in a new grammar and raises an exception.
# How do we get around this?

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Loco.php';

final class LeftRecursionTest extends TestCase
{
    public function testLeftRecursion(): void
    {
        # N -> number
        $N = new Ferno\Loco\RegexParser(
            "#^(0|[1-9][0-9]*)#",
            function ($match) {
                return (int) $match;
            }
        );

        # P -> "-" N
        $P = new Ferno\Loco\ConcParser(
            array(new Ferno\Loco\StringParser("-"), $N),
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
            $grammar = new Ferno\Loco\Grammar(
                "S",
                array(
                    "S" => new Ferno\Loco\LazyAltParser(
                        array(
                            "N",
                            new Ferno\Loco\ConcParser(
                                array("S", "P"),
                                "minus"
                            )
                        )
                    ),
                    "P" => $P,
                    "N" => $N
                )
            );
        } catch (Ferno\Loco\GrammarException $e) {
            # Left-recursive in S
            $threw = true;
        }
        $this->assertEquals($threw, true);

        # Fix the grammar like so:
        # S -> N P*
        $grammar = new Ferno\Loco\Grammar(
            "S",
            array(
                "S" => new Ferno\Loco\ConcParser(
                    array(
                        $N,
                        new Ferno\Loco\GreedyStarParser("P")
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
