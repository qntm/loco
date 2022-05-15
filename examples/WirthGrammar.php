<?php
require_once __DIR__ . '/../src/Loco.php';

// Takes a string presented in Wirth syntax notation and turn it into a new
// Grammar object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Wirth_syntax_notation

$wirthGrammar = new Ferno\Loco\Grammar(
    "SYNTAX",
    array(
        "SYNTAX" => new Ferno\Loco\GreedyStarParser("PRODUCTION"),
        "PRODUCTION" => new Ferno\Loco\ConcParser(
            array(
                "whitespace",
                "IDENTIFIER",
                new Ferno\Loco\StringParser("="),
                "whitespace",
                "EXPRESSION",
                new Ferno\Loco\StringParser("."),
                "whitespace"
            ),
            function ($space1, $identifier, $equals, $space2, $expression, $dot, $space3) {
                return array("identifier" => $identifier, "expression" => $expression);
            }
        ),
        "EXPRESSION" => new Ferno\Loco\ConcParser(
            array(
                "TERM",
                new Ferno\Loco\GreedyStarParser(
                    new Ferno\Loco\ConcParser(
                        array(
                            new Ferno\Loco\StringParser("|"),
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
                return new Ferno\Loco\LazyAltParser($terms);
            }
        ),
        "TERM" => new Ferno\Loco\GreedyMultiParser(
            "FACTOR",
            1,
            null,
            function () {
                return new Ferno\Loco\ConcParser(func_get_args());
            }
        ),
        "FACTOR" => new Ferno\Loco\LazyAltParser(
            array(
                "IDENTIFIER",
                "LITERAL",
                new Ferno\Loco\ConcParser(
                    array(
                        new Ferno\Loco\StringParser("["),
                        "whitespace",
                        "EXPRESSION",
                        new Ferno\Loco\StringParser("]"),
                        "whitespace"
                    ),
                    function ($bracket1, $space1, $expression, $bracket2, $space2) {
                        return new Ferno\Loco\GreedyMultiParser($expression, 0, 1);
                    }
                ),
                new Ferno\Loco\ConcParser(
                    array(
                        new Ferno\Loco\StringParser("("),
                        "whitespace",
                        "EXPRESSION",
                        new Ferno\Loco\StringParser(")"),
                        "whitespace"
                    ),
                    function ($paren1, $space1, $expression, $paren2, $space2) {
                        return $expression;
                    }
                ),
                new Ferno\Loco\ConcParser(
                    array(
                        new Ferno\Loco\StringParser("{"),
                        "whitespace",
                        "EXPRESSION",
                        new Ferno\Loco\StringParser("}"),
                        "whitespace"
                    ),
                    function ($brace1, $space1, $expression, $brace2, $space2) {
                        return new Ferno\Loco\GreedyStarParser($expression);
                    }
                )
            )
        ),
        "IDENTIFIER" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\GreedyMultiParser(
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
        "LITERAL" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("\""),
                new Ferno\Loco\GreedyMultiParser(
                    "character",
                    1,
                    null,
                    function () {
                        return implode("", func_get_args());
                    }
                ),
                new Ferno\Loco\StringParser("\""),
                "whitespace"
            ),
            function ($quote1, $chars, $quote2, $whitespace) {
                return new Ferno\Loco\StringParser($chars);
            }
        ),
        "digit" => new Ferno\Loco\RegexParser("#^[0-9]#"),
        "letter" => new Ferno\Loco\RegexParser("#^[a-zA-Z]#"),
        "character" => new Ferno\Loco\RegexParser(
            "#^([^\"]|\"\")#",
            function ($match0) {
                if ($match0 === "\"\"") {
                    return "\"";
                }
                return $match0;
            }
        ),
        "whitespace" => new Ferno\Loco\RegexParser("#^[ \n\r\t]*#")
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
            throw new Exception("No rules.");
        }
        return new Ferno\Loco\Grammar($top, $parsers);
    }
);
