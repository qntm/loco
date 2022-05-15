<?php
require_once __DIR__ . '/../../src/Loco.php';
require_once __DIR__ . '/Charclass.php';
require_once __DIR__ . '/Multiplier.php';
require_once __DIR__ . '/Mult.php';
require_once __DIR__ . '/Conc.php';
require_once __DIR__ . '/Pattern.php';

$regexGrammar = new Ferno\Loco\Grammar(
    "<pattern>",
    array(
        // A Pattern is an alternation between several Concs, separated by pipes.
        "<pattern>" => new Ferno\Loco\ConcParser(
            array("<conc>", "<pipeconclist>"),
            function ($conc, $pipeconclist) {
                array_unshift($pipeconclist, $conc);
                return new Pattern($pipeconclist);
            }
        ),
        "<pipeconclist>" => new Ferno\Loco\GreedyStarParser(
            "<pipeconc>"
        ),
        "<pipeconc>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("|"),
                "<conc>"
            ),
            function ($pipe, $conc) {
                return $conc;
            }
        ),

        // A Conc is a concatenation of several Mults.
        "<conc>" => new Ferno\Loco\GreedyStarParser(
            "<mult>",
            function () {
                return new Conc(func_get_args());
            }
        ),

        // A Mult is a multiplicand (Charclass or sub-Pattern) followed by a multiplier.
        // A subpattern has to be put inside parentheses.
        "<mult>" => new Ferno\Loco\ConcParser(
            array("<multiplicand>", "<multiplier>"),
            function ($multiplicand, $multiplier) {
                return new Mult($multiplicand, $multiplier);
            }
        ),
        "<multiplicand>" => new Ferno\Loco\LazyAltParser(
            array("<subpattern>", "<charclass>")
        ),
        "<subpattern>" => new Ferno\Loco\ConcParser(
            array(new Ferno\Loco\StringParser("("), "<pattern>", new Ferno\Loco\StringParser(")")),
            function ($left_parenthesis, $pattern, $right_parenthesis) {
                return $pattern;
            }
        ),

        // A Multiplier has a lower bound and an upper bound. There are several short forms.
        // In the absence of a multiplier, {1,1} is assumed
        "<multiplier>" => new Ferno\Loco\LazyAltParser(
            array(
                "<bracemultiplier>",
                new Ferno\Loco\StringParser("?", function ($string) {
                    return new Multiplier(0, 1);
                }),
                new Ferno\Loco\StringParser("*", function ($string) {
                    return new Multiplier(0, null);
                }),
                new Ferno\Loco\StringParser("+", function ($string) {
                    return new Multiplier(1, null);
                }),
                new Ferno\Loco\EmptyParser(function () {
                    return new Multiplier(1, 1);
                })
            )
        ),

        "<bracemultiplier>" => new Ferno\Loco\ConcParser(
            array(
                new Ferno\Loco\StringParser("{"),
                "<multiplierinterior>",
                new Ferno\Loco\StringParser("}")
            ),
            function ($left_brace, $multiplierinterior, $right_brace) {
                return $multiplierinterior;
            }
        ),

        "<multiplierinterior>" => new Ferno\Loco\LazyAltParser(
            array("<bothbounds>", "<unlimited>", "<onebound>")
        ),
        "<bothbounds>" => new Ferno\Loco\ConcParser(
            array("<integer>", "COMMA", "<integer>"),
            function ($integer1, $comma, $integer2) {
                return new Multiplier($integer1, $integer2);
            }
        ),
        "<unlimited>" => new Ferno\Loco\ConcParser(
            array("<integer>", "COMMA"),
            function ($integer, $comma) {
                return new Multiplier($integer, null);
            }
        ),
        "<onebound>" => new Ferno\Loco\ConcParser(
            array("<integer>"),
            function ($integer) {
                return new Multiplier($integer, $integer);
            }
        ),
        "COMMA" => new Ferno\Loco\StringParser(","),
        "<integer>" => new Ferno\Loco\RegexParser("#^(0|[1-9][0-9]*)#", function ($match) {
            return (int)($match);
        }),

        // A Charclass is usually a single literal character.
        // It can also be a single character escaped with a backslash,
        // or a "true" charclass, which is a possibly-negated set of elements
        // listed inside a pair of brackets.
        "<charclass>" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\RegexParser("#^[^|()\\[\\]?*+{}\\\\.]#", function ($match) {
                    return new Charclass($match);
                }),
                "<bracketednegatedcharclass>",
                "<bracketedcharclass>",
                new Ferno\Loco\StringParser("\\|", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\(", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\)", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\[", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\]", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\?", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\*", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\+", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\{", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\}", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\\\", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\.", function ($string) {
                    return new Charclass(substr($string, 1, 1));
                }),
                new Ferno\Loco\StringParser("\\f", function ($string) {
                    return new Charclass("\f");
                }),
                new Ferno\Loco\StringParser("\\n", function ($string) {
                    return new Charclass("\n");
                }),
                new Ferno\Loco\StringParser("\\r", function ($string) {
                    return new Charclass("\r");
                }),
                new Ferno\Loco\StringParser("\\t", function ($string) {
                    return new Charclass("\t");
                }),
                new Ferno\Loco\StringParser("\\v", function ($string) {
                    return new Charclass("\v");
                }),
                new Ferno\Loco\StringParser("\\w", function ($string) {
                    return new Charclass("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz");
                }),
                new Ferno\Loco\StringParser("\\W", function ($string) {
                    return new Charclass("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz", true);
                }),
                new Ferno\Loco\StringParser("\\d", function ($string) {
                    return new Charclass("0123456789");
                }),
                new Ferno\Loco\StringParser("\\D", function ($string) {
                    return new Charclass("0123456789", true);
                }),
                new Ferno\Loco\StringParser("\\s", function ($string) {
                    return new Charclass(" \f\n\r\t\v");
                }),
                new Ferno\Loco\StringParser("\\S", function ($string) {
                    return new Charclass(" \f\n\r\t\v", true);
                }),
                new Ferno\Loco\StringParser(".", function ($string) {
                    return new Charclass("", true);
                })
            )
        ),

        "<bracketednegatedcharclass>" => new Ferno\Loco\ConcParser(
            array("LEFT_BRACKET", "CARET", "<elemlist>", "RIGHT_BRACKET"),
            function ($left_bracket, $elemlist, $right_bracket) {
                return new Charclass($elemlist, true);
            }
        ),
        "<bracketedcharclass>" => new Ferno\Loco\ConcParser(
            array("LEFT_BRACKET", "<elemlist>", "RIGHT_BRACKET"),
            function ($left_bracket, $elemlist, $right_bracket) {
                return new Charclass($elemlist);
            }
        ),
        "LEFT_BRACKET" => new Ferno\Loco\StringParser("["),
        "RIGHT_BRACKET" => new Ferno\Loco\StringParser("]"),
        "CARET" => new Ferno\Loco\StringParser("^"),

        // A true charclass may be negated with a leading caret.
        "<elemlist>" => new Ferno\Loco\GreedyStarParser(
            "<elem>",
            function () {
                return implode("", func_get_args());
            }
        ),

        // An element is either a single character or a character range.
        // A character range is represented with an optional hyphen
        "<elem>" => new Ferno\Loco\LazyAltParser(
            array("<charrange>", "<classchar>")
        ),

        "<charrange>" => new Ferno\Loco\ConcParser(
            array("<classchar>", "HYPHEN", "<classchar>"),
            function ($char1, $hyphen, $char2) {
                $char1 = ord($char1);
                $char2 = ord($char2);
                if ($char2 < $char1) {
                    throw new Exception("Disordered range");
                }
                $string = "";
                for ($ord = $char1; $ord <= $char2; $ord++) {
                    $string .= chr($ord);
                }
                return $string;
            }
        ),
        "HYPHEN" => new Ferno\Loco\StringParser("-"),

        // interior characters in character classes usually represent themselves,
        // but some are backslash-escaped
        "<classchar>" => new Ferno\Loco\LazyAltParser(
            array(
                new Ferno\Loco\RegexParser("#^[^\\\\\\[\\]\\^\\-]#"),
                new Ferno\Loco\StringParser("\\\\", function ($string) {
                    return substr($string, 1, 1);
                }),
                new Ferno\Loco\StringParser("\\[", function ($string) {
                    return substr($string, 1, 1);
                }),
                new Ferno\Loco\StringParser("\\]", function ($string) {
                    return substr($string, 1, 1);
                }),
                new Ferno\Loco\StringParser("\\^", function ($string) {
                    return substr($string, 1, 1);
                }),
                new Ferno\Loco\StringParser("\\-", function ($string) {
                    return substr($string, 1, 1);
                }),
                new Ferno\Loco\StringParser("\\f", function ($string) {
                    return "\f";
                }),
                new Ferno\Loco\StringParser("\\n", function ($string) {
                    return "\n";
                }),
                new Ferno\Loco\StringParser("\\r", function ($string) {
                    return "\r";
                }),
                new Ferno\Loco\StringParser("\\t", function ($string) {
                    return "\t";
                }),
                new Ferno\Loco\StringParser("\\v", function ($string) {
                    return "\v";
                })
            )
        )
    )
);
