<?php


namespace ferno\loco\grammar;


use Exception;
use ferno\loco\ConcParser;
use ferno\loco\EmptyParser;
use ferno\loco\Grammar;
use ferno\loco\GreedyMultiParser;
use ferno\loco\GreedyStarParser;
use ferno\loco\LazyAltParser;
use ferno\loco\RegexParser;
use ferno\loco\StringParser;

/**
 * Takes a string presented in Backus-Naur Form and turns it into a new Grammar
 * object capable of recognising the language described by that string.
 * @link http://en.wikipedia.org/wiki/Backus%E2%80%93Naur_Form
 */

# This code is in the public domain.
# http://qntm.org/locoparser
class BnfGrammar extends Grammar {
    public function __construct() {
        parent::__construct(
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
    }
} 