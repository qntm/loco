<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;
use Ferno\Loco\JsonGrammar;

// apologies for the relative lack of exhaustive unit tests

final class JsonGrammarTest extends TestCase
{
    private static $jsonGrammar;

    public static function setUpBeforeClass(): void
    {
        self::$jsonGrammar = new JsonGrammar();
    }

    public function testBasic(): void
    {
        $parseTree = self::$jsonGrammar->parse(
            " { \"string\" : true, \"\\\"\" : false, \"\\u9874asdh\" : [ null, { }, -9488.44E+093 ] } "
        );

        $this->assertEquals(count($parseTree), 3);
        $this->assertEquals($parseTree["string"], true);
        $this->assertEquals($parseTree["\""], false);
        $this->assertEquals($parseTree["\xE9\xA1\xB4asdh"], array(null, array(), -9.48844E+96));
    }

    public function testFailureModes(): void
    {
        foreach (array(
            "{ \"string ",        // incomplete string
            "{ \"\\UAAAA\" ",     // capital U on unicode char
            "{ \"\\u000i\" ",     // not enough hex digits on unicode char
            "{ \"a\" : tru ",     // incomplete "true"
            "{ \"a\" :  +9 ",     // leading +
            "{ \"a\" :  9. ",     // missing decimal digits
            "{ \"a\" :  0a8.52 ", // extraneous "a"
            "{ \"a\" :  8E ",     // missing exponent
            "{ \"a\" :  08 ",     // Two numbers side by side.
            "[ \"a\" ,  8 ]",     // Not an object at the top level.
            " \"a\" ",            // Not an object at the top level.
            "{\"\x00\"    :7}",   // string contains a literal control character
            "{\"\xC2\x9F\":7}",   // string contains a literal control character
            "{\"\n\"      :7}",   // string contains a literal control character
            "{\"\r\"      :7}",   // string contains a literal control character
            "{\"\t\"      :7}"    // string contains a literal control character
        ) as $string) {
            $threw = false;
            try {
                self::$jsonGrammar->parse($string);
            } catch (ParseFailureException $e) {
                $threw = true;
            }
            $this->assertEquals($threw, true);
        }
    }
}
