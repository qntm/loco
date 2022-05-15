<?php
require_once __DIR__ . '/../src/Loco.php';

// Takes a string presented in Backus-Naur Form and turns it into a new Grammar
// object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Backus%E2%80%93Naur_Form

$bnfGrammar = new Ferno\Loco\Grammar(
    "<syntax>",
    array(
        "<syntax>" => new Ferno\Loco\ConcParser(
            array(
                "<rules>",
                "OPT-WHITESPACE"
            ),
            function ($rules, $whitespace) {
                return $rules;
            }
        ),

        "<rules>" => new Ferno\Loco\GreedyMultiParser(
            "<ruleoremptyline>",
            1,
            null,
            function () {
                $rules = array();
                foreach (func_get_args() as $rule) {
                    // blank line
                    if ($rule === null) {
                        continue;
                    }

                    $rules[] = $rule;
                }
                return $rules;
            }
        ),

        "<ruleoremptyline>" => new Ferno\Loco\LazyAltParser(
            array("<rule>", "<emptyline>")
        ),

        "<emptyline>" => new Ferno\Loco\ConcParser(
            array("OPT-WHITESPACE", "EOL"),
            function ($whitespace, $eol) {
                return null;
            }
        ),

        "<rule>" => new Ferno\Loco\ConcParser(
            array(
                "OPT-WHITESPACE",
                "RULE-NAME",
                "OPT-WHITESPACE",
                new Ferno\Loco\StringParser("::="),
                "OPT-WHITESPACE",
                "<expression>",
                "EOL"
            ),
            function (
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

        "<expression>" => new Ferno\Loco\ConcParser(
            array(
                "<list>",
                "<pipelists>"
            ),
            function ($list, $pipelists) {
                array_unshift($pipelists, $list);
                return new Ferno\Loco\LazyAltParser($pipelists);
            }
        ),

        "<pipelists>" => new Ferno\Loco\GreedyStarParser("<pipelist>"),

        "<pipelist>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("|"),
                "OPT-WHITESPACE",
                "<list>"
            ),
            function ($pipe, $whitespace, $list) {
                return $list;
            }
        ),

        "<list>" => new Ferno\Loco\GreedyMultiParser(
            "<term>",
            1,
            null,
            function () {
                return new Ferno\Loco\ConcParser(func_get_args());
            }
        ),

        "<term>" => new Ferno\Loco\ConcParser(
            array("TERM", "OPT-WHITESPACE"),
            function ($term, $whitespace) {
                return $term;
            }
        ),

        "TERM" => new Ferno\Loco\LazyAltParser(
            array(
                "LITERAL",
                "RULE-NAME"
            )
        ),

        "LITERAL" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\RegexParser('#^"([^"]*)"#', function ($match0, $match1) {
                    return $match1;
                }),
                new Ferno\Loco\RegexParser("#^'([^']*)'#", function ($match0, $match1) {
                    return $match1;
                })
            ),
            function ($text) {
                if ($text == "") {
                    return new Ferno\Loco\EmptyParser(function () {
                        return "";
                    });
                }
                return new Ferno\Loco\StringParser($text);
            }
        ),

        "RULE-NAME" => new Ferno\Loco\RegexParser("#^<[A-Za-z\\-]*>#"),

        "OPT-WHITESPACE" => new Ferno\Loco\RegexParser("#^[\t ]*#"),

        "EOL" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\StringParser("\r"),
                new Ferno\Loco\StringParser("\n")
            )
        )
    ),
    function ($syntax) {
        $parsers = array();
        foreach ($syntax as $rule) {
            if (count($parsers) === 0) {
                $top = $rule["rule-name"];
            }
            $parsers[$rule["rule-name"]] = $rule["expression"];
        }
        if (count($parsers) === 0) {
            throw new Ferno\Loco\Exception("No rules.");
        }
        return new Ferno\Loco\Grammar($top, $parsers);
    }
);
