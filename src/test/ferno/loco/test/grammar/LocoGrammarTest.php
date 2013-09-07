<?php

namespace ferno\loco\test\parser\grammar;

use ferno\loco\grammar\LocoGrammar;
use ferno\loco\ParseFailureException;
use PHPUnit_Framework_TestCase as TestCase;

class LocoGrammarTest extends TestCase
{
    /** @var LocoGrammar */
    private $grammar;

    public function setUp()
    {
        $this->grammar = new LocoGrammar();
    }

    public function testBasic()
    {
        // array("a") or new S("a")
        $grammar2 = $this->grammar->parse(" S ::= 'a' ");
        $this->assertEquals(array("a"), $grammar2->parse("a"));
    }

    public function testConcat()
    {
        // array("a", "b") or new S("a", "b")
        $grammar2 = $this->grammar->parse(" S ::= 'a' 'b' ");
        $this->assertEquals(array("a", "b"), $grammar2->parse("ab"));
    }

    public function testAlternation()
    {
        // array("a") or array("b") or new S("a") or new S("b")
        $grammar2 = $this->grammar->parse(" S ::= 'a' | 'b' ");
        $this->assertEquals(array("a"), $grammar2->parse("a"));
        $this->assertEquals(array("b"), $grammar2->parse("b"));
    }

    public function testALternation2()
    {
        // array("a") or array("b", "c") or new S("a") or new S("b", "c")
        $grammar2 = $this->grammar->parse(" S ::= 'a' | 'b' 'c' ");
        $this->assertEquals(array("a"), $grammar2->parse("a"));
        $this->assertEquals(array("b", "c"), $grammar2->parse("bc"));
    }

    public function testSubparsers()
    {
        // array(array("a") or new S(array("a"))
        $grammar2 = $this->grammar->parse(" S ::= ('a') ");
        $this->assertEquals(array(array("a")), $grammar2->parse("a"));
    }

    public function testChains()
    {
        // new S(new A("a"))
        $grammar1 = $this->grammar->parse(" S ::= A \n A ::= 'a' ");
        $this->assertEquals(array(array("a")), $grammar1->parse("a"));
    }

    public function testChains2()
    {
        // new S(new A("a", "b"))
        $grammar1 = $this->grammar->parse(" S ::= A \n A ::= 'a' 'b' ");
        $this->assertEquals(array(array("a", "b")), $grammar1->parse("ab"));
    }

    public function testQuestionMarkMultiplier()
    {
        // Question mark multiplier
        // new S("a") or new S()
        $grammar1 = $this->grammar->parse(" S ::= 'a'? ");
        $this->assertEquals(array("a"), $grammar1->parse("a"));
        $this->assertEquals(array(), $grammar1->parse(""));

        $grammar2 = $this->grammar->parse(" S ::= 'a' | ");
        // new S(new AQ("a")) or new S(new AQ())
        $grammar3 = $this->grammar->parse(" S ::= ( 'a' | ) ");
        $grammar4 = $this->grammar->parse(" S ::= AQ \n AQ ::= 'a' | ");

        $this->assertEquals(array("a"), $grammar2->parse("a"));
        $this->assertEquals(array(array("a")), $grammar3->parse("a"));
        $this->assertEquals(array(array("a")), $grammar4->parse("a"));

        $this->assertEquals(array(), $grammar2->parse(""));
        $this->assertEquals(array(array()), $grammar3->parse(""));
        $this->assertEquals(array(array()), $grammar4->parse(""));
    }

    public function testStarParser()
    {
        // Star parser
        // new S("a", "a", ...)
        // array("a", "a", ...)
        $grammar1 = $this->grammar->parse(" S ::= 'a'* ");
        $grammar4 = $this->grammar->parse(" S ::= 'a' 'a' 'a' | 'a' 'a' | 'a' | ");
        // new S(array("a", "a", ...))
        // new S(new AStar("a", "a", ...))
        $grammar2 = $this->grammar->parse(" S ::= ( 'a' 'a' 'a' | 'a' 'a' | 'a' | ) ");
        $grammar3 = $this->grammar->parse(" S ::= AStar \n AStar ::= 'a' 'a' 'a' | 'a' 'a' | 'a' | ");
        $this->assertEquals(array("a", "a", "a"), $grammar1->parse("aaa"));
        $this->assertEquals(array("a", "a", "a"), $grammar4->parse("aaa"));
        $this->assertEquals(array(array("a", "a", "a")), $grammar2->parse("aaa"));
        $this->assertEquals(array(array("a", "a", "a")), $grammar3->parse("aaa"));
        $this->assertEquals(array("a", "a"), $grammar1->parse("aa"));
        $this->assertEquals(array("a", "a"), $grammar4->parse("aa"));
        $this->assertEquals(array(array("a", "a")), $grammar2->parse("aa"));
        $this->assertEquals(array(array("a", "a")), $grammar3->parse("aa"));
        $this->assertEquals(array("a"), $grammar1->parse("a"));
        $this->assertEquals(array("a"), $grammar4->parse("a"));
        $this->assertEquals(array(array("a")), $grammar2->parse("a"));
        $this->assertEquals(array(array("a")), $grammar3->parse("a"));
        $this->assertEquals(array(), $grammar1->parse(""));
        $this->assertEquals(array(), $grammar4->parse(""));
        $this->assertEquals(array(array()), $grammar2->parse(""));
        $this->assertEquals(array(array()), $grammar3->parse(""));

    }

    public function testPlusParser()
    {
        // Plus parser
        // new S(array("a", "a", ...))
        // new S(new APlus("a", "a", ...))
        $grammar1 = $this->grammar->parse(" S ::= 'a'+ ");
        $grammar4 = $this->grammar->parse(" S ::= 'a' 'a' 'a' | 'a' 'a' | 'a' ");
        $grammar2 = $this->grammar->parse(" S ::= ( 'a' 'a' 'a' | 'a' 'a' | 'a' ) ");
        $grammar3 = $this->grammar->parse(" S ::= APlus \n APlus ::= 'a' 'a' 'a' | 'a' 'a' | 'a' ");

        $this->assertEquals(array("a", "a", "a"), $grammar1->parse("aaa"));
        $this->assertEquals(array("a", "a", "a"), $grammar4->parse("aaa"));
        $this->assertEquals(array(array("a", "a", "a")), $grammar2->parse("aaa"));
        $this->assertEquals(array(array("a", "a", "a")), $grammar3->parse("aaa"));
        $this->assertEquals(array("a", "a"), $grammar1->parse("aa"));
        $this->assertEquals(array("a", "a"), $grammar4->parse("aa"));
        $this->assertEquals(array(array("a", "a")), $grammar2->parse("aa"));
        $this->assertEquals(array(array("a", "a")), $grammar3->parse("aa"));
        $this->assertEquals(array("a"), $grammar1->parse("a"));
        $this->assertEquals(array("a"), $grammar4->parse("a"));
        $this->assertEquals(array(array("a")), $grammar2->parse("a"));
        $this->assertEquals(array(array("a")), $grammar3->parse("a"));

        try {
            $grammar1->parse("");
            $this->assertFalse(true, "Exception was not thrown");
        } catch (ParseFailureException $e) {

        }
        try {
            $grammar4->parse("");
            $this->assertFalse(true, "Exception was not thrown");
        } catch (ParseFailureException $e) {

        }
        try {
            $grammar2->parse("");
            $this->assertFalse(true, "Exception was not thrown");
        } catch (ParseFailureException $e) {
        }
        try {
            $grammar3->parse("");
            $this->assertFalse(true, "Exception was not thrown");
        } catch (ParseFailureException $e) {
        }
    }

    public function testRegexes()
    {
        // Regexes and Leaning Toothpic Syndrome
        $grammar1 = $this->grammar->parse(" S ::= /(ab)*/ ");
        $this->assertEquals(array("ababab"), $grammar1->parse("ababab"));
        $grammar = $this->grammar->parse(" number ::= /a\\.b/ ");
        $this->assertEquals(array("a.b"), $grammar->parse("a.b"));

        $this->setExpectedException(ParseFailureException::_CLASS);
        $grammar1->parse("aXb");
    }

    public function testLiteralSlash()
    {
        // parse a literal slash
        $grammar1 = $this->grammar->parse(" S ::= /\\// ");
        $this->assertEquals(array("/"), $grammar1->parse("/"));
    }

    public function testLiteralBackslash()
    {
        // parse a literal backslash
        $grammar1 = $this->grammar->parse(" S ::= /\\\\/ ");
        $this->assertEquals(array("\\"), $grammar1->parse("\\"));

    }

    public function failingDotCases()
    {
        return array(
            array(''), // Empty.
            array("\xF4\x90\x80\x80"), // code point U+110000, out of range (max is U+10FFFF)
            array("\xC0\xA6"), // overlong encoding (code point is U+26; should be 1 byte, "\x26")
            array("\xC3\xFF"), // illegal continuation byte,
            array("\xFF"), // illegal leading byte
            array("\xC2"), // mid-character termination
            array("\x00"), // null
            array("\xED\xA0\x80"), // 55296d
            array("\xED\xBF\xBF"), // 57343d
        );
    }

    /**
     * @dataProvider failingDotCases
     * @param string $input
     */
    public function testDotFailureCases($input)
    {
        $grammar = $this->grammar->parse(" S ::= . ");

        $this->setExpectedException(ParseFailureException::_CLASS);
        $grammar->parse($input);
    }

    public function testDot()
    {
        // UTF-8 dot (equivalent to [^], if you think about it!)
        $grammar = $this->grammar->parse(" S ::= . ");

        $this->assertEquals(array("\x41"), $grammar->parse("\x41")); // 1-byte character "A"
        $this->assertEquals(array("\xC2\xAF"), $grammar->parse("\xC2\xAF")); // 2-byte character "�"
        $this->assertEquals(array("\xE2\x99\xA5"), $grammar->parse("\xE2\x99\xA5")); // 3-byte character "?"
        $this->assertEquals(array("\xF1\x8B\x81\x82"), $grammar->parse("\xF1\x8B\x81\x82")); // 4-byte character "??"
        $this->assertEquals(array("\xEF\xBB\xBF"), $grammar->parse("\xEF\xBB\xBF")); // "byte order mark" 11101111 10111011 10111111 (U+FEFF)
        $this->assertEquals(array("\xF0\x90\x80\x80"), $grammar->parse("\xF0\x90\x80\x80")); // 4-byte character
        $this->assertEquals(array("\xF0\xA0\x80\x80"), $grammar->parse("\xF0\xA0\x80\x80")); // 4-byte character
    }

    public function failingUtfCases()
    {
        return array(
            array("&"),
            array(" "),
            array("<"),
            array(">"),
            array("]"),
        );
    }

    /**
     * @dataProvider failingUtfCases
     * @param string $input
     */
    public function testFailingUtfCases($input)
    {
        $grammar = $this->grammar->parse(" S ::= [^& <>\\]] ");
        $this->setExpectedException(ParseFailureException::_CLASS);

        $grammar->parse($input);
    }

    public function utfLongForm()
    {
        // UTF-8 long form with exceptions
        $grammar = $this->grammar->parse(" S ::= [^& <>\\]] ");
        $this->assertEquals(array("A"), $grammar->parse("A"));
        $this->assertEquals(array("^"), $grammar->parse("^"));
        $this->assertEquals(array("\\"), $grammar->parse("\\"));
    }

    public function testTwoRules()
    {
        $grammar2 = $this->grammar->parse(
            "unicode ::= 'a' '' b | 'grammar' '\\'' '\\\\' \"\\\"\"
            b::="
        );

        $result = $grammar2->parse("grammar'\\\"");
        $this->assertEquals(array("grammar", "'", "\\", "\""), $result);
    }

    public function balancedBrackets()
    {
        return array(
            array(''),
            array('<>'),
            array('<><>'),
            array('<<>>'),
            array("<<<><>>><><<>>"),
        );
    }

    public function unbalancedBrackets()
    {
        return array(
            array(' '),
            array('<'),
            array('>'),
            array('<><'),
            array('<<>'),
            array('<<<><>>><><><<<<>>>>>'),
        );
    }

    /**
     * @dataProvider balancedBrackets
     */
    public function testBracketMatching($input)
    {
        $bracketMatchGrammar = $this->grammar->parse(
            "S          ::= expression *
            expression ::= '<' S '>'"
        );

        // TODO: verify
        $bracketMatchGrammar->parse($input);
    }

    /**
     * @dataProvider unbalancedBrackets
     */
    public function testUnbalancedBracketMatching($input)
    {
        $bracketMatchGrammar = $this->grammar->parse(
            "S          ::= expression *
            expression ::= '<' S '>'"
        );

        $this->setExpectedException(ParseFailureException::_CLASS);
        $bracketMatchGrammar->parse($input);
    }

    private $json_parser = "
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
    ";

    public function testJson()
    {
        // Full rules for recognising JSON
        $jsonGrammar = $this->grammar->parse($this->json_parser);

        $result = $jsonGrammar->parse(" { \"string\" : true, \"\\\"\" : false, \"\\u9874asdh\" : [ null, { }, -9488.44E+093 ] } ");
    }

    public function badJson()
    {
        return array(
            array("{ \"string "), // incomplete string
            array("{ \"\\UAAAA\" "), // capital U on unicode char
            array("{ \"\\u000i\" "), // not enough hex digits on unicode char
            array("{ \"a\" : tru "), // incomplete "true"
            array("{ \"a\" :  +9 "), // leading +
            array("{ \"a\" :  9. "), // missing decimal digits
            array("{ \"a\" :  0a8.52 "), // extraneous "a"
            array("{ \"a\" :  8E "), // missing exponent
            array("{ \"a\" :  08 "), // Two numbers side by side.
        );
    }

    /**
     * @dataProvider badJson
     */
    public function testBadJson($input)
    {
        $jsonGrammar = $this->grammar->parse($this->json_parser);
        $this->setExpectedException(ParseFailureException::_CLASS);
        $result = $jsonGrammar->parse($input);
    }

    private $simpleCommentSyntax = "
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
    ";

    public function testCommentGrammar()
    {
        $simpleCommentGrammar = $this->grammar->parse($this->simpleCommentSyntax);

        $string = $simpleCommentGrammar->parse(
            "<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>
            \r\n\t
            <p  >&lt;</p  >
            "
        );
    }

    public function commentSyntaxFailureCases()
    {
        return array(
            array("<h5 style=\"\">"), // rogue "style" attribute
            array("&"), // unescaped AMPERSAND
            array("<"), // unescaped LESS_THAN
            array("salkhsfg>"), // unescaped GREATER_THAN
            array("</p"), // incomplete CLOSE_P
            array("<br"), // incomplete FULL_BR
        );
    }

    /**
     * @dataProvider commentSyntaxFailureCases
     */
    public function testCommentSyntaxFailureCases($input)
    {
        $simpleCommentGrammar = $this->grammar->parse($this->simpleCommentSyntax);

        $this->setExpectedException('Exception');
        $string = $simpleCommentGrammar->parse($input);
    }
}
