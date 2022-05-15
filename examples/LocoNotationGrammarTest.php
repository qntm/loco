<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/LocoNotationGrammar.php';

final class LocoNotationGrammarTest extends TestCase
{
    public function testBasic(): void
    {
        // array("a") or new S("a")
        global $locoGrammar;
        $grammar2 = $locoGrammar->parse(" S ::= 'a' ");
        $this->assertEquals($grammar2->parse("a"), array("a"));
    }

    public function testConcatenation(): void
    {
        // array("a", "b") or new S("a", "b")
        global $locoGrammar;
        $grammar2 = $locoGrammar->parse(" S ::= 'a' 'b' ");
        $this->assertEquals($grammar2->parse("ab"), array("a", "b"));
    }

    public function testAlternation(): void
    {
        // array("a") or array("b") or new S("a") or new S("b")
        global $locoGrammar;
        $grammar2 = $locoGrammar->parse(" S ::= 'a' | 'b' ");
        $this->assertEquals($grammar2->parse("a"), array("a"));
        $this->assertEquals($grammar2->parse("b"), array("b"));
    }

    public function testAlternation2(): void
    {
        // array("a") or array("b", "c") or new S("a") or new S("b", "c")
        global $locoGrammar;
        $grammar2 = $locoGrammar->parse(" S ::= 'a' | 'b' 'c' ");
        $this->assertEquals($grammar2->parse("a"), array("a"));
        $this->assertEquals($grammar2->parse("bc"), array("b", "c"));
    }

    public function testSubParsers(): void
    {
        // array(array("a") or new S(array("a"))
        global $locoGrammar;
        $grammar2 = $locoGrammar->parse(" S ::= ('a') ");
        $this->assertEquals($grammar2->parse("a"), array(array("a")));
    }

    public function testSa(): void
    {
        // new S(new A("a"))
        global $locoGrammar;
        $grammar1 = $locoGrammar->parse(" S ::= A \n A ::= 'a' ");
        $this->assertEquals($grammar1->parse("a"), array(array("a")));
    }

    public function testSab(): void
    {
        // new S(new A("a", "b"))
        global $locoGrammar;
        $grammar1 = $locoGrammar->parse(" S ::= A \n A ::= 'a' 'b' ");
        $this->assertEquals($grammar1->parse("ab"), array(array("a", "b")));
    }

    public function testQuestionMarkMultiplier(): void
    {
        global $locoGrammar;

        // new S("a") or new S()
        $grammar1 = $locoGrammar->parse(" S ::= 'a'? ");
        $grammar2 = $locoGrammar->parse(" S ::= 'a' | ");
        // new S(new AQ("a")) or new S(new AQ())
        $grammar3 = $locoGrammar->parse(" S ::= ( 'a' | ) ");
        $grammar4 = $locoGrammar->parse(" S ::= AQ \n AQ ::= 'a' | ");
        $this->assertEquals($grammar1->parse("a"), array(array("a")));
        $this->assertEquals($grammar2->parse("a"), array("a"));
        $this->assertEquals($grammar3->parse("a"), array(array("a")));
        $this->assertEquals($grammar4->parse("a"), array(array("a")));
        $this->assertEquals($grammar1->parse(""), array(array()));
        $this->assertEquals($grammar2->parse(""), array());
        $this->assertEquals($grammar3->parse(""), array(array()));
        $this->assertEquals($grammar4->parse(""), array(array()));
    }

    public function testStarParser(): void
    {
        global $locoGrammar;

        // new S("a", "a", ...)
        // array("a", "a", ...)
        $grammar1 = $locoGrammar->parse(" S ::= 'a'* ");
        $grammar4 = $locoGrammar->parse(" S ::= 'a' 'a' 'a' | 'a' 'a' | 'a' | ");
        // new S(array("a", "a", ...))
        // new S(new AStar("a", "a", ...))
        $grammar2 = $locoGrammar->parse(" S ::= ( 'a' 'a' 'a' | 'a' 'a' | 'a' | ) ");
        $grammar3 = $locoGrammar->parse(" S ::= AStar \n AStar ::= 'a' 'a' 'a' | 'a' 'a' | 'a' | ");
        $this->assertEquals($grammar1->parse("aaa"), array(array("a", "a", "a")));
        $this->assertEquals($grammar4->parse("aaa"), array("a", "a", "a"));
        $this->assertEquals($grammar2->parse("aaa"), array(array("a", "a", "a")));
        $this->assertEquals($grammar3->parse("aaa"), array(array("a", "a", "a")));
        $this->assertEquals($grammar1->parse("aa"), array(array("a", "a")));
        $this->assertEquals($grammar4->parse("aa"), array("a", "a"));
        $this->assertEquals($grammar2->parse("aa"), array(array("a", "a")));
        $this->assertEquals($grammar3->parse("aa"), array(array("a", "a")));
        $this->assertEquals($grammar1->parse("a"), array(array("a")));
        $this->assertEquals($grammar4->parse("a"), array("a"));
        $this->assertEquals($grammar2->parse("a"), array(array("a")));
        $this->assertEquals($grammar3->parse("a"), array(array("a")));
        $this->assertEquals($grammar1->parse(""), array(array()));
        $this->assertEquals($grammar4->parse(""), array());
        $this->assertEquals($grammar2->parse(""), array(array()));
        $this->assertEquals($grammar3->parse(""), array(array()));
    }

    public function testPlusParser(): void
    {
        global $locoGrammar;

        // new S(array("a", "a", ...))
        // new S(new APlus("a", "a", ...))
        $grammar1 = $locoGrammar->parse(" S ::= 'a'+ ");
        $grammar4 = $locoGrammar->parse(" S ::= 'a' 'a' 'a' | 'a' 'a' | 'a' ");
        $grammar2 = $locoGrammar->parse(" S ::= ( 'a' 'a' 'a' | 'a' 'a' | 'a' ) ");
        $grammar3 = $locoGrammar->parse(" S ::= APlus \n APlus ::= 'a' 'a' 'a' | 'a' 'a' | 'a' ");
        $this->assertEquals($grammar1->parse("aaa"), array(array("a", "a", "a")));
        $this->assertEquals($grammar4->parse("aaa"), array("a", "a", "a"));
        $this->assertEquals($grammar2->parse("aaa"), array(array("a", "a", "a")));
        $this->assertEquals($grammar3->parse("aaa"), array(array("a", "a", "a")));
        $this->assertEquals($grammar1->parse("aa"), array(array("a", "a")));
        $this->assertEquals($grammar4->parse("aa"), array("a", "a"));
        $this->assertEquals($grammar2->parse("aa"), array(array("a", "a")));
        $this->assertEquals($grammar3->parse("aa"), array(array("a", "a")));
        $this->assertEquals($grammar1->parse("a"), array(array("a")));
        $this->assertEquals($grammar4->parse("a"), array("a"));
        $this->assertEquals($grammar2->parse("a"), array(array("a")));
        $this->assertEquals($grammar3->parse("a"), array(array("a")));

        $threw = false;
        try {
            $grammar1->parse("");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar4->parse("");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar2->parse("");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar3->parse("");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testRegexes(): void
    {
        global $locoGrammar;

        $grammar1 = $locoGrammar->parse(" S ::= /(ab)*/ ");
        $this->assertEquals($grammar1->parse("ababab"), array("ababab"));
        $grammar = $locoGrammar->parse(" number ::= /a\\.b/ ");
        $this->assertEquals($grammar->parse("a.b"), array("a.b"));

        $threw = false;
        try {
            $grammar1->parse("aXb");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testLiteralSlash(): void
    {
        global $locoGrammar;
        $grammar1 = $locoGrammar->parse(" S ::= /\\// ");
        $this->assertEquals($grammar1->parse("/"), array("/"));
    }

    public function testLiteralBackslash(): void
    {
        global $locoGrammar;
        $grammar1 = $locoGrammar->parse(" S ::= /\\\\/ ");
        $this->assertEquals($grammar1->parse("\\"), array("\\"));
    }

    public function testDot(): void
    {
        global $locoGrammar;

        $grammar1 = $locoGrammar->parse(" S ::= . ");

        $threw = false;
        try {
            $grammar1->parse("");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($grammar1->parse("\x41"), array("\x41")); # 1-byte character "A"
        $this->assertEquals($grammar1->parse("\xC2\xAF"), array("\xC2\xAF")); # 2-byte character "�"
        $this->assertEquals($grammar1->parse("\xE2\x99\xA5"), array("\xE2\x99\xA5")); # 3-byte character "?"
        $this->assertEquals($grammar1->parse("\xF1\x8B\x81\x82"), array("\xF1\x8B\x81\x82")); # 4-byte character "??"
        $this->assertEquals($grammar1->parse("\xEF\xBB\xBF"), array("\xEF\xBB\xBF")); # "byte order mark" 11101111 10111011 10111111 (U+FEFF)
        $this->assertEquals($grammar1->parse("\xF0\x90\x80\x80"), array("\xF0\x90\x80\x80")); # 4-byte character
        $this->assertEquals($grammar1->parse("\xF0\xA0\x80\x80"), array("\xF0\xA0\x80\x80")); # 4-byte character

        $threw = false;
        try {
            $grammar1->parse("\xF4\x90\x80\x80"); # code point U+110000, out of range (max is U+10FFFF)
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\xC0\xA6"); # overlong encoding (code point is U+26; should be 1 byte, "\x26")
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\xC3\xFF"); # illegal continuation byte
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\xFF"); # illegal leading byte
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\xC2"); # mid-character termination
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\x00"); # null
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\xED\xA0\x80"); # 55296d
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("\xED\xBF\xBF"); # 57343d
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testUtf8Long(): void
    {
        global $locoGrammar;

        $grammar1 = $locoGrammar->parse(" S ::= [^& <>\\]] ");
        $this->assertEquals($grammar1->parse("A"), array("A"));
        $this->assertEquals($grammar1->parse("^"), array("^"));
        $this->assertEquals($grammar1->parse("\\"), array("\\"));

        $threw = false;
        try {
            $grammar1->parse("&");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse(" ");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("<");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse(">");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar1->parse("]");
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testTwoRules(): void
    {
        global $locoGrammar;
        $grammar2 = $locoGrammar->parse("
            unicode ::= 'a' '' b | 'grammar' '\\'' '\\\\' \"\\\"\"
            b::=
        ");
        $result = $grammar2->parse("grammar'\\\"");
        $this->assertEquals($result, array("grammar", "'", "\\", "\""));
    }

    public function testBracketMatching(): void
    {
        global $locoGrammar;

        $bracketMatchGrammar = $locoGrammar->parse("
            S          ::= expression *
            expression ::= '<' S '>'
        ");

        foreach (array(
            "",
            "<>",
            "<><>",
            "<<>>",
            "<<<><>>><><<>>"
        ) as $string) {
            $bracketMatchGrammar->parse($string);
        }

        foreach (array(
            " ",
            "<",
            ">",
            "<><",
            "<<>",
            "<<<><>>><><><<<<>>>>>"
        ) as $string) {
            $threw = false;
            try {
                $bracketMatchGrammar->parse($string);
            } catch (Ferno\Loco\ParseFailureException $e) {
                $threw = true;
            }
            $this->assertEquals($threw, true);
        }
    }

    public function testFullJson(): void
    {
        global $locoGrammar;

        // Full rules for recognising JSON
        $jsonGrammar = $locoGrammar->parse("
            topobject      ::= whitespace object
            object         ::= '{' whitespace objectcontent '}' whitespace
            objectcontent  ::= fullobject | ()
            fullobject     ::= keyvalue (comma keyvalue)*
            keyvalue       ::= string ':' whitespace value
            array          ::= '[' whitespace arraycontent ']' whitespace
            arraycontent   ::= fullarray | ()
            fullarray      ::= value (comma value)*
            value          ::= string | number | object | array | true | false | null
            string         ::= '\"' stringcontent '\"' whitespace
            stringcontent  ::= char *
            char           ::= [^\"\\\\] | '\\\\' escapesequence
            escapesequence ::= '\"' | '\\\\' | '/' | 'b' | 'f' | 'n' | 'r' | 't' | /u[0-9a-fA-F]{4}/
            number         ::= /-?(0|[1-9][0-9]*)(\\.[0-9]+)?([eE][-+]?[0-9]+)?/ whitespace
            true           ::= 'true' whitespace
            false          ::= 'false' whitespace
            null           ::= 'null' whitespace
            comma          ::= ',' whitespace
            whitespace     ::= /[ \n\r\t]*/
        ");

        $result = $jsonGrammar->parse(" { \"string\" : true, \"\\\"\" : false, \"\\u9874asdh\" : [ null, { }, -9488.44E+093 ] } ");

        foreach (array(
            "{ \"string ",        // incomplete string
            "{ \"\\UAAAA\" ",     // capital U on unicode char
            "{ \"\\u000i\" ",     // not enough hex digits on unicode char
            "{ \"a\" : tru ",     // incomplete "true"
            "{ \"a\" :  +9 ",     // leading +
            "{ \"a\" :  9. ",     // missing decimal digits
            "{ \"a\" :  0a8.52 ", // extraneous "a"
            "{ \"a\" :  8E ",     // missing exponent
            "{ \"a\" :  08 "      // Two numbers side by side.
        ) as $string) {
            $threw = false;
            try {
                $jsonGrammar->parse($string);
            } catch (Exception $e) {
                $threw = true;
            }
            $this->assertEquals($threw, true);
        }
    }

    public function testQntmComments(): void
    {
        global $locoGrammar;

        $simpleCommentGrammar = $locoGrammar->parse("
            comment    ::= whitespace block*
            block      ::= h5 whitespace | p whitespace
            p          ::= '<p'      whitespace '>' text '</p'      whitespace '>'
            h5         ::= '<h5'     whitespace '>' text '</h5'     whitespace '>'
            strong     ::= '<strong' whitespace '>' text '</strong' whitespace '>'
            em         ::= '<em'     whitespace '>' text '</em'     whitespace '>'
            br         ::= '<br'     whitespace '/>'
            text       ::= atom*
            atom       ::= [^<>&] | '&' entity ';' | strong | em | br
            entity     ::= 'gt' | 'lt' | 'amp'
            whitespace ::= /[ \n\r\t]*/
        ");

        $string = $simpleCommentGrammar->parse("
            <h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>
            \r\n\t
            <p  >&lt;</p  >
        ");

        foreach (array(
            "<h5 style=\"\">", // rogue "style" attribute
            "&",               // unescaped AMPERSAND
            "<",               // unescaped LESS_THAN
            "salkhsfg>",       // unescaped GREATER_THAN
            "</p",             // incomplete CLOSE_P
            "<br"              // incomplete FULL_BR
        ) as $string) {
            $threw = false;
            try {
                $simpleCommentGrammar->parse($string);
            } catch (Ferno\Loco\ParseFailureException $e) {
                $threw = true;
            }
            $this->assertEquals($threw, true);
        }
    }
}
