<?php
require_once __DIR__ . '/../src/Loco.php';

$simpleCommentGrammar = new Ferno\Loco\Grammar(
    "<comment>",
    array(
        "<comment>" => new Ferno\Loco\GreedyStarParser(
            "<blockorwhitespace>",
            function () {
                return implode("", func_get_args());
            }
        ),
        "<blockorwhitespace>" => new Ferno\Loco\LazyAltParser(
            array("<h5>", "<p>", "WHITESPACE")
        ),
        "<p>" => new Ferno\Loco\ConcParser(
            array("OPEN_P", "<text>", "CLOSE_P"),
            function ($open_p, $text, $close_p) {
                return $open_p.$text.$close_p;
            }
        ),
        "<h5>" => new Ferno\Loco\ConcParser(
            array("OPEN_H5", "<text>", "CLOSE_H5"),
            function ($open_h5, $text, $close_h5) {
                return $open_h5.$text.$close_h5;
            }
        ),
        "<strong>" => new Ferno\Loco\ConcParser(
            array("OPEN_STRONG", "<text>", "CLOSE_STRONG"),
            function ($open_strong, $text, $close_strong) {
                return $open_strong.$text.$close_strong;
            }
        ),
        "<em>" => new Ferno\Loco\ConcParser(
            array("OPEN_EM", "<text>", "CLOSE_EM"),
            function ($open_em, $text, $close_em) {
                return $open_em.$text.$close_em;
            }
        ),
        "<text>" => new Ferno\Loco\GreedyStarParser(
            "<atom>",
            function () {
                return implode("", func_get_args());
            }
        ),
        "<atom>" => new Ferno\Loco\LazyAltParser(
            array("<char>", "<strong>", "<em>", "FULL_BR")
        ),
        "<char>" => new Ferno\Loco\LazyAltParser(
            array("UTF8_EXCEPT", "GREATER_THAN", "LESS_THAN", "AMPERSAND")
        ),

        # actual lexables here

        "WHITESPACE"   => new Ferno\Loco\RegexParser("#^[ \n\r\t]+#"),
        "OPEN_P"       => new Ferno\Loco\RegexParser("#^<p[ \n\r\t]*>#"),
        "CLOSE_P"      => new Ferno\Loco\RegexParser("#^</p[ \n\r\t]*>#"),
        "OPEN_H5"      => new Ferno\Loco\RegexParser("#^<h5[ \n\r\t]*>#"),
        "CLOSE_H5"     => new Ferno\Loco\RegexParser("#^</h5[ \n\r\t]*>#"),
        "OPEN_EM"      => new Ferno\Loco\RegexParser("#^<em[ \n\r\t]*>#"),
        "CLOSE_EM"     => new Ferno\Loco\RegexParser("#^</em[ \n\r\t]*>#"),
        "OPEN_STRONG"  => new Ferno\Loco\RegexParser("#^<strong[ \n\r\t]*>#"),
        "CLOSE_STRONG" => new Ferno\Loco\RegexParser("#^</strong[ \n\r\t]*>#"),
        "FULL_BR"      => new Ferno\Loco\RegexParser("#^<br[ \n\r\t]*/>#"),

        "UTF8_EXCEPT"  => new Ferno\Loco\Utf8Parser(array("<", ">", "&")), // any UTF-8 character except <, > or &
        "GREATER_THAN" => new Ferno\Loco\StringParser("&gt;"),             // ... or an escaped >
        "LESS_THAN"    => new Ferno\Loco\StringParser("&lt;"),             // ... or an escaped <
        "AMPERSAND"    => new Ferno\Loco\StringParser("&amp;"),            // ... or an escaped &
    )
);
