<?php declare(strict_types=1);
// Takes a string presented in Wirth syntax notation and turn it into a new
// Grammar object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Wirth_syntax_notation

namespace Ferno\Loco\examples;

use Ferno\Loco\Grammar;
use Ferno\Loco\GreedyStarParser;
use Ferno\Loco\ConcParser;
use Ferno\Loco\StringParser;
use Ferno\Loco\GreedyMultiParser;
use Ferno\Loco\LazyAltParser;
use Ferno\Loco\RegexParser;
use Ferno\Loco\ParseFailureException;

final class WirthGrammar extends Grammar
{
    public function __construct()
    {
        parent::__construct(
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
                    function ($space1, $identifier, $equals, $space2, $expression, $dot, $space3) {
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
                                function ($pipe, $space, $term) {
                                    return $term;
                                }
                            )
                        )
                    ),
                    function ($term, $terms) {
                        array_unshift($terms, $term);
                        return new LazyAltParser($terms);
                    }
                ),
                "TERM" => new GreedyMultiParser(
                    "FACTOR",
                    1,
                    null,
                    function () {
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
                            function ($bracket1, $space1, $expression, $bracket2, $space2) {
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
                            function ($paren1, $space1, $expression, $paren2, $space2) {
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
                            function ($brace1, $space1, $expression, $brace2, $space2) {
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
                            function () {
                                return implode("", func_get_args());
                            }
                        ),
                        "whitespace",
                    ),
                    function ($letters, $whitespace) {
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
                            function () {
                                return implode("", func_get_args());
                            }
                        ),
                        new StringParser("\""),
                        "whitespace"
                    ),
                    function ($quote1, $chars, $quote2, $whitespace) {
                        return new StringParser($chars);
                    }
                ),
                "digit" => new RegexParser("#^[0-9]#"),
                "letter" => new RegexParser("#^[a-zA-Z]#"),
                "character" => new RegexParser(
                    "#^([^\"]|\"\")#",
                    function ($match0) {
                        if ($match0 === "\"\"") {
                            return "\"";
                        }
                        return $match0;
                    }
                ),
                "whitespace" => new RegexParser("#^[ \n\r\t]*#")
            ),
            function ($syntax) {
                $parsers = array();
                foreach ($syntax as $production) {
                    if (count($parsers) === 0) {
                        $top = $production["identifier"];
                    }
                    $parsers[$production["identifier"]] = $production["expression"];
                }
                if (count($parsers) === 0) {
                    throw new ParseFailureException("No rules.");
                }
                return new Grammar($top, $parsers);
            }
        );
    }
}
