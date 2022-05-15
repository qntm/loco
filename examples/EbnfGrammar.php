<?php
// Takes a string presented in Extended Backus-Naur Form and turns it into a new Grammar
// object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_Form

// Can't handle exceptions, since these are not context-free
// Can't handle specials, which have no clear definition

$ebnfGrammar = new Ferno\Loco\Grammar(
    "<syntax>",
    array(
        "<syntax>" => new Ferno\Loco\ConcParser(
            array("<space>", "<rules>"),
            function ($space, $rules) {
                return $rules;
            }
        ),

        "<rules>" => new Ferno\Loco\GreedyStarParser("<rule>"),

        "<rule>" => new Ferno\Loco\ConcParser(
            array("<bareword>", "<space>", new Ferno\Loco\StringParser("="), "<space>", "<alt>", new Ferno\Loco\StringParser(";"), "<space>"),
            function ($bareword, $space1, $equals, $space2, $alt, $semicolon, $space3) {
                return array(
                    "rule-name"  => $bareword,
                    "expression" => $alt
                );
            }
        ),

        "<alt>" => new Ferno\Loco\ConcParser(
            array("<conc>", "<pipeconclist>"),
            function ($conc, $pipeconclist) {
                array_unshift($pipeconclist, $conc);
                return new Ferno\Loco\LazyAltParser($pipeconclist);
            }
        ),

        "<pipeconclist>" => new Ferno\Loco\GreedyStarParser("<pipeconc>"),

        "<pipeconc>" => new Ferno\Loco\ConcParser(
            array(new Ferno\Loco\StringParser("|"), "<space>", "<conc>"),
            function ($pipe, $space, $conc) {
                return $conc;
            }
        ),

        "<conc>" => new Ferno\Loco\ConcParser(
            array("<term>", "<commatermlist>"),
            function ($term, $commatermlist) {
                array_unshift($commatermlist, $term);

                // get array key numbers where multiparsers are located
                // in reverse order so that our splicing doesn't modify the array
                $multiparsers = array();
                foreach ($commatermlist as $k => $internal) {
                    if (is_a($internal, "GreedyMultiParser")) {
                        array_unshift($multiparsers, $k);
                    }
                }

                // We do something quite advanced here. The inner multiparsers are
                // spliced out into the list of arguments proper instead of forming an
                // internal sub-array of their own
                return new Ferno\Loco\ConcParser(
                    $commatermlist,
                    function () use ($multiparsers) {
                        $args = func_get_args();
                        foreach ($multiparsers as $k) {
                            array_splice($args, $k, 1, $args[$k]);
                        }
                        return $args;
                    }
                );
            }
        ),

        "<commatermlist>" => new Ferno\Loco\GreedyStarParser("<commaterm>"),

        "<commaterm>" => new Ferno\Loco\ConcParser(
            array(new Ferno\Loco\StringParser(","), "<space>", "<term>"),
            function ($comma, $space, $term) {
                return $term;
            }
        ),

        "<term>" => new Ferno\Loco\LazyAltParser(
            array("<bareword>", "<sq>", "<dq>", "<group>", "<repetition>", "<optional>")
        ),

        "<bareword>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\RegexParser(
                    "#^([a-z][a-z ]*[a-z]|[a-z])#",
                    function ($match0) {
                        return $match0;
                    }
                ),
                "<space>"
            ),
            function ($bareword, $space) {
                return $bareword;
            }
        ),

        "<sq>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\RegexParser(
                    "#^'([^']*)'#",
                    function ($match0, $match1) {
                        if ($match1 === "") {
                            return new Ferno\Loco\EmptyParser();
                        }
                        return new Ferno\Loco\StringParser($match1);
                    }
                ),
                "<space>"
            ),
            function ($string, $space) {
                return $string;
            }
        ),

        "<dq>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\RegexParser(
                    '#^"([^"]*)"#',
                    function ($match0, $match1) {
                        if ($match1 === "") {
                            return new Ferno\Loco\EmptyParser();
                        }
                        return new Ferno\Loco\StringParser($match1);
                    }
                ),
                "<space>"
            ),
            function ($string, $space) {
                return $string;
            }
        ),

        "<group>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("("),
                "<space>",
                "<alt>",
                new Ferno\Loco\StringParser(")"),
                "<space>"
            ),
            function ($left_paren, $space1, $alt, $right_paren, $space2) {
                return $alt;
            }
        ),

        "<repetition>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("{"),
                "<space>",
                "<alt>",
                new Ferno\Loco\StringParser("}"),
                "<space>"
            ),
            function ($left_brace, $space1, $alt, $right_brace, $space2) {
                return new Ferno\Loco\GreedyStarParser($alt);
            }
        ),

        "<optional>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("["),
                "<space>",
                "<alt>",
                new Ferno\Loco\StringParser("]"),
                "<space>"
            ),
            function ($left_bracket, $space1, $alt, $right_bracket, $space2) {
                return new Ferno\Loco\GreedyMultiParser($alt, 0, 1);
            }
        ),

        "<space>" => new Ferno\Loco\GreedyStarParser("<whitespace/comment>"),

        "<whitespace/comment>" => new Ferno\Loco\LazyAltParser(
            array("<whitespace>", "<comment>")
        ),

        "<whitespace>" => new Ferno\Loco\RegexParser("#^[ \t\r\n]+#"),
        "<comment>" => new Ferno\Loco\RegexParser("#^(\(\* [^*]* \*\)|\(\* \*\)|\(\*\*\))#")
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
            throw new Exception("No rules.");
        }
        return new Ferno\Loco\Grammar($top, $parsers);
    }
);
