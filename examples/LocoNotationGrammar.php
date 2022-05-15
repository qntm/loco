<?php
// Takes a string presented in Loco Backus-Naur Form and turns it into a
// new Grammar object capable of recognising the language described by that string.

$locoGrammar = new Ferno\Loco\Grammar(
    "<grammar>",
    array(
        "<grammar>" => new Ferno\Loco\ConcParser(
            array("<whitespace>", "<rules>"),
            function ($whitespace, $rules) {
                return $rules;
            }
        ),

        "<rules>" => new Ferno\Loco\GreedyStarParser(
            "<ruleorblankline>",
            function () {
                $rules = array();
                foreach (func_get_args() as $ruleorblankline) {
                    if ($ruleorblankline === null) {
                        continue;
                    }
                    $rules[] = $ruleorblankline;
                }
                return $rules;
            }
        ),

        "<ruleorblankline>" => new Ferno\Loco\LazyAltParser(
            array("<rule>", "<blankline>")
        ),

        "<blankline>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\RegexParser("#^\r?\n#"),
                "<whitespace>"
            ),
            function () {
                return null;
            }
        ),

        "<rule>" => new Ferno\Loco\ConcParser(
            array(
                "<bareword>",
                "<whitespace>",
                new Ferno\Loco\StringParser("::="),
                "<whitespace>",
                "<lazyaltparser>"
            ),
            function ($bareword, $whitespace1, $equals, $whitespace2, $lazyaltparser) {
                return array(
                    "name" => $bareword,
                    "lazyaltparser" => $lazyaltparser
                );
            }
        ),

        "<lazyaltparser>" => new Ferno\Loco\ConcParser(
            array("<concparser>", "<pipeconcparserlist>"),
            function ($concparser, $pipeconcparserlist) {
                array_unshift($pipeconcparserlist, $concparser);

                // make a basic lazyaltparser which returns whatever.
                // Since the LazyAltParser always contains 0 or more ConcParsers,
                // the value of $result is always an array
                return new Ferno\Loco\LazyAltParser(
                    $pipeconcparserlist
                );
            }
        ),

        "<pipeconcparserlist>" => new Ferno\Loco\GreedyStarParser("<pipeconcparser>"),

        "<pipeconcparser>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("|"),
                "<whitespace>",
                "<concparser>"
            ),
            function ($pipe, $whitespace, $concparser) {
                return $concparser;
            }
        ),

        "<concparser>" => new Ferno\Loco\GreedyStarParser(
            "<bnfmultiplication>",
            function () {
                // get array key numbers where multiparsers are located
                // in reverse order so that our splicing doesn't modify the array
                $multiparsers = array();
                foreach (func_get_args() as $k => $internal) {
                    if (is_a($internal, "GreedyMultiParser")) {
                        array_unshift($multiparsers, $k);
                    }
                }

                // We do something quite advanced here. The inner multiparsers are
                // spliced out into the list of arguments proper instead of forming an
                // internal sub-array of their own
                return new Ferno\Loco\ConcParser(
                    func_get_args(),
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

        "<bnfmultiplication>" => new Ferno\Loco\ConcParser(
            array("<bnfmultiplicand>", "<whitespace>", "<bnfmultiplier>", "<whitespace>"),
            function ($bnfmultiplicand, $whitespace1, $bnfmultiplier, $whitespace2) {
                if (is_array($bnfmultiplier)) {
                    return new Ferno\Loco\GreedyMultiParser(
                        $bnfmultiplicand,
                        $bnfmultiplier["lower"],
                        $bnfmultiplier["upper"]
                    );
                }

                // otherwise assume multiplier = 1
                return $bnfmultiplicand;
            }
        ),

        "<bnfmultiplicand>" => new Ferno\Loco\LazyAltParser(
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

        "<bnfmultiplier>" => new Ferno\Loco\LazyAltParser(
            array("<asterisk>", "<plus>", "<questionmark>", "<emptymultiplier>")
        ),

        "<asterisk>" => new Ferno\Loco\StringParser(
            "*",
            function () {
                return array("lower" => 0, "upper" => null);
            }
        ),

        "<plus>" => new Ferno\Loco\StringParser(
            "+",
            function () {
                return array("lower" => 1, "upper" => null);
            }
        ),

        "<questionmark>" => new Ferno\Loco\StringParser(
            "?",
            function () {
                return array("lower" => 0, "upper" => 1);
            }
        ),

        "<emptymultiplier>" => new Ferno\Loco\EmptyParser(),

        // return a basic parser which recognises this string
        "<dqstringparser>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("\""),
                "<dqstring>",
                new Ferno\Loco\StringParser("\"")
            ),
            function ($quote1, $string, $quote2) {
                if ($string === "") {
                    return new Ferno\Loco\EmptyParser();
                }
                return new Ferno\Loco\StringParser($string);
            }
        ),

        "<sqstringparser>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("'"),
                "<sqstring>",
                new Ferno\Loco\StringParser("'")
            ),
            function ($apostrophe1, $string, $apostrophe2) {
                if ($string === "") {
                    return new Ferno\Loco\EmptyParser();
                }
                return new Ferno\Loco\StringParser($string);
            }
        ),

        "<dqstring>" => new Ferno\Loco\GreedyStarParser(
            "<dqstrchar>",
            function () {
                return implode("", func_get_args());
            }
        ),

        "<sqstring>" => new Ferno\Loco\GreedyStarParser(
            "<sqstrchar>",
            function () {
                return implode("", func_get_args());
            }
        ),

        "<dqstrchar>" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\Utf8Parser(array("\\", "\"")),
                new Ferno\Loco\StringParser("\\\\", function ($string) {
                    return "\\";
                }),
                new Ferno\Loco\StringParser('\\"', function ($string) {
                    return '"';
                })
            )
        ),

        "<sqstrchar>" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\Utf8Parser(array("\\", "'")),
                new Ferno\Loco\StringParser("\\\\", function ($string) {
                    return "\\";
                }),
                new Ferno\Loco\StringParser("\\'", function ($string) {
                    return "'";
                })
            )
        ),

        // return a basic parser matching this regex
        "<regexparser>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("/"),
                "<regex>",
                new Ferno\Loco\StringParser("/")
            ),
            function ($slash1, $regex, $slash2) {
                if ($regex === "") {
                    return new Ferno\Loco\EmptyParser();
                }

                // Add the anchor and the brackets to make sure it anchors in the
                // correct location
                $regex = "/^(".$regex.")/";
                // print("Actual regex is: ".$regex."\n");
                return new Ferno\Loco\RegexParser($regex);
            }
        ),

        "<regex>" => new Ferno\Loco\GreedyStarParser(
            "<rechar>",
            function () {
                return implode("", func_get_args());
            }
        ),

        // Regular expression contains: Any single character that is not a slash or backslash...
        // OR any single character escaped by a backslash. Return as literal.
        "<rechar>" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\Utf8Parser(array("\\", "/")),
                new Ferno\Loco\ConcParser(
                    array(
                        new Ferno\Loco\StringParser("\\"),
                        new Ferno\Loco\Utf8Parser()
                    ),
                    function ($backslash, $char) {
                        return $backslash.$char;
                    }
                )
            )
        ),

        "<utf8except>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("[^"),
                "<exceptions>",
                new Ferno\Loco\StringParser("]")
            ),
            function ($left_bracket_caret, $exceptions, $right_bracket) {
                return new Ferno\Loco\Utf8Parser($exceptions);
            }
        ),

        "<exceptions>" => new Ferno\Loco\GreedyStarParser("<exceptionchar>"),

        "<exceptionchar>" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\Utf8Parser(array("\\", "]")),
                new Ferno\Loco\StringParser("\\\\", function ($string) {
                    return "\\";
                }),
                new Ferno\Loco\StringParser("\\]", function ($string) {
                    return "]";
                })
            )
        ),

        "<utf8parser>" => new Ferno\Loco\StringParser(
            ".",
            function () {
                return new Ferno\Loco\Utf8Parser(array());
            }
        ),

        "<subparser>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("("),
                "<whitespace>",
                "<lazyaltparser>",
                new Ferno\Loco\StringParser(")")
            ),
            function ($left_parenthesis, $whitespace1, $lazyaltparser, $right_parenthesis) {
                return $lazyaltparser;
            }
        ),

        "<whitespace>" => new Ferno\Loco\RegexParser("#^[ \t]*#"),
        "<bareword>"   => new Ferno\Loco\RegexParser("#^[a-zA-Z_][a-zA-Z0-9_]*#")
    ),
    function ($rules) {
        $parsers = array();
        foreach ($rules as $rule) {
            if (count($parsers) === 0) {
                $top = $rule["name"];
            }
            $parsers[$rule["name"]] = $rule["lazyaltparser"];
        }
        return new Ferno\Loco\Grammar($top, $parsers);
    }
);
