<?php
require_once __DIR__ . '/../src/Loco.php';

$jsonGrammar = new Ferno\Loco\Grammar(
    "<topobject>",
    array(
        "<topobject>" => new Ferno\Loco\ConcParser(
            array("WHITESPACE", "<object>"),
            function ($whitespace, $object) {
                return $object;
            }
        ),

        "<object>" => new Ferno\Loco\ConcParser(
            array("LEFT_BRACE", "WHITESPACE", "<objectcontent>", "RIGHT_BRACE", "WHITESPACE"),
            function ($left_brace, $whitespace0, $objectcontent, $right_brace, $whitespace1) {
                return $objectcontent;
            }
        ),

        "<objectcontent>" => new Ferno\Loco\LazyAltParser(
            array("<fullobject>", "<emptyobject>")
        ),

        "<fullobject>" => new Ferno\Loco\ConcParser(
            array("<keyvalue>", "<commakeyvaluelist>"),
            function ($keyvalue, $commakeyvaluelist) {
                $commakeyvaluelist[$keyvalue[0]] = $keyvalue[1];
                return $commakeyvaluelist;
            }
        ),

        "<emptyobject>" => new Ferno\Loco\EmptyParser(
            function () {
                return array();
            }
        ),

        "<commakeyvaluelist>" => new Ferno\Loco\GreedyStarParser(
            "<commakeyvalue>",
            function () {
                $commakeyvaluelist = array();
                foreach (func_get_args() as $commakeyvalue) {
                    $commakeyvaluelist[$commakeyvalue[0]] = $commakeyvalue[1];
                }
                return $commakeyvaluelist;
            }
        ),

        "<commakeyvalue>" => new Ferno\Loco\ConcParser(
            array("COMMA", "WHITESPACE", "<keyvalue>"),
            function ($comma, $whitespace, $keyvalue) {
                return $keyvalue;
            }
        ),

        "<keyvalue>" => new Ferno\Loco\ConcParser(
            array("<string>", "COLON", "WHITESPACE", "<value>"),
            function ($string, $colon, $whitespace, $value) {
                return array($string, $value);
            }
        ),

        "<array>" => new Ferno\Loco\ConcParser(
            array("LEFT_BRACKET", "WHITESPACE", "<arraycontent>", "RIGHT_BRACKET", "WHITESPACE"),
            function ($left_bracket, $whitespace0, $arraycontent, $right_bracket, $whitespace1) {
                return $arraycontent;
            }
        ),

        "<arraycontent>" => new Ferno\Loco\LazyAltParser(
            array("<fullarray>", "<emptyarray>")
        ),

        "<fullarray>" => new Ferno\Loco\ConcParser(
            array("<value>", "<commavaluelist>"),
            function ($value, $commavaluelist) {
                array_unshift($commavaluelist, $value);
                return $commavaluelist;
            }
        ),

        "<emptyarray>" => new Ferno\Loco\EmptyParser(
            function () {
                return array();
            }
        ),

        "<commavaluelist>" => new Ferno\Loco\GreedyStarParser("<commavalue>"),

        "<commavalue>" => new Ferno\Loco\ConcParser(
            array("COMMA", "WHITESPACE", "<value>"),
            function ($comma, $whitespace, $value) {
                return $value;
            }
        ),

        "<value>" => new Ferno\Loco\LazyAltParser(
            array("<string>", "<number>", "<object>", "<array>", "<true>", "<false>", "<null>")
        ),

        "<string>" => new Ferno\Loco\ConcParser(
            array("DOUBLE_QUOTE", "<stringcontent>", "DOUBLE_QUOTE", "WHITESPACE"),
            function ($double_quote0, $stringcontent, $double_quote1, $whitespace) {
                return $stringcontent;
            }
        ),

        "<stringcontent>" => new Ferno\Loco\GreedyStarParser(
            "<char>",
            function () {
                return implode("", func_get_args());
            }
        ),

        "<char>" => new Ferno\Loco\LazyAltParser(
            array(
                "UTF8_EXCEPT", "ESCAPED_QUOTE", "ESCAPED_BACKSLASH", "ESCAPED_SLASH", "ESCAPED_B",
                "ESCAPED_F", "ESCAPED_N", "ESCAPED_R", "ESCAPED_T", "ESCAPED_UTF8"
            )
        ),

        "<number>" => new Ferno\Loco\ConcParser(array("NUMBER", "WHITESPACE"), function ($number, $whitespace) {
            return $number;
        }),
        "<true>" => new Ferno\Loco\ConcParser(array("TRUE", "WHITESPACE"), function ($true, $whitespace) {
            return true;
        }),
        "<false>" => new Ferno\Loco\ConcParser(array("FALSE", "WHITESPACE"), function ($false, $whitespace) {
            return false;
        }),
        "<null>" => new Ferno\Loco\ConcParser(array("NULL", "WHITESPACE"), function ($null, $whitespace) {
            return null;
        }),

        # actual physical objects (RegexParsers, StringParsers and Utf8Parsers)
        # are represented in all capitals because they are important.
        # this is effectively the lexer portion of the whole shebang.

        "WHITESPACE" => new Ferno\Loco\ RegexParser("#^[ \n\r\t]*#"), // ignored
        "LEFT_BRACE" => new Ferno\Loco\StringParser("{"),             // ignored
        "RIGHT_BRACE" => new Ferno\Loco\StringParser("}"),            // ignored
        "LEFT_BRACKET" => new Ferno\Loco\StringParser("["),           // ignored
        "RIGHT_BRACKET" => new Ferno\Loco\StringParser("]"),          // ignored
        "COLON" => new Ferno\Loco\StringParser(":"),                  // ignored
        "COMMA" => new Ferno\Loco\StringParser(","),                  // ignored
        "DOUBLE_QUOTE" => new Ferno\Loco\StringParser("\""),          // ignored

        "NUMBER" => new Ferno\Loco\ RegexParser("#^-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][-+]?[0-9]+)?#", function ($match) {
            return (float)$match;
        }),
        "TRUE" => new Ferno\Loco\StringParser("true"),
        "FALSE" => new Ferno\Loco\StringParser("false"),
        "NULL" => new Ferno\Loco\StringParser("null"),

        // "Any UNICODE character except..."
        "UTF8_EXCEPT" => new Ferno\Loco\Utf8Parser(
            array_merge(
                // "double quote or backslash..."
                array("\"", "\\"),
                // "or control character"
                array_map(
                    function ($codepoint) {
                        return Ferno\Loco\Utf8Parser::getBytes($codepoint);
                    },
                    Ferno\Loco\Utf8Parser::$controls
                )
            )
        ),
        "ESCAPED_QUOTE" => new Ferno\Loco\StringParser("\\\"", function ($string) {
            return substr($string, 1, 1);
        }),
        "ESCAPED_BACKSLASH" => new Ferno\Loco\StringParser("\\\\", function ($string) {
            return substr($string, 1, 1);
        }),
        "ESCAPED_SLASH" => new Ferno\Loco\StringParser("\\/", function ($string) {
            return substr($string, 1, 1);
        }),
        "ESCAPED_B" => new Ferno\Loco\StringParser("\\b", function ($string) {
            return "\x08";
        }),
        "ESCAPED_F" => new Ferno\Loco\StringParser("\\f", function ($string) {
            return "\f";
        }),
        "ESCAPED_N" => new Ferno\Loco\StringParser("\\n", function ($string) {
            return "\n";
        }),
        "ESCAPED_R" => new Ferno\Loco\StringParser("\\r", function ($string) {
            return "\r";
        }),
        "ESCAPED_T" => new Ferno\Loco\StringParser("\\t", function ($string) {
            return "\t";
        }),
        "ESCAPED_UTF8" => new Ferno\Loco\ RegexParser("#^\\\\u[0-9a-fA-F]{4}#", function ($match) {
            return Ferno\Loco\Utf8Parser::getBytes(hexdec(substr($match, 2, 4)));
        })
    )
);
