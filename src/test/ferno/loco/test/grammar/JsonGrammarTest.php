<?php

namespace ferno\loco\test\parser\grammar;

use ferno\loco\grammar\JsonGrammar;
use PHPUnit_Framework_TestCase as TestCase;

class JsonGrammarTest extends TestCase
{
    private $grammar;

    public function setUp()
    {
        $this->grammar = new JsonGrammar();
    }

    public function testSimple()
    {
        $expected = array(
            'string' => true,
            '"' => false,
            "\xE9\xA1\xB4asdh" => array(
                null,
                array(),
                -9.48844E+96
            )
        );

        $this->assertEquals($expected, $this->grammar->parse(' { "string" : true, "\"" : false, "\\u9874asdh" : [ null, { }, -9488.44E+093 ] } '));
    }

    public function failureModes()
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
            array("[ \"a\" ,  8 ]"), // Not an object at the top level.
            array(" \"a\" "), // Not an object at the top level.
            array("{\"\x00\"    :7}"), // string contains a literal control character
            array("{\"\xC2\x9F\":7}"), // string contains a literal control character
            array("{\"\n\"      :7}"), // string contains a literal control character
            array("{\"\r\"      :7}"), // string contains a literal control character
            array("{\"\t\"      :7}"), // string contains a literal control character
        );
    }

    /**
     * @dataProvider failureModes
     * @param string $input the string to test
     */
    public function testFailureModes($input)
    {
        $this->setExpectedException('Exception');
        $this->grammar->parse($input);
    }
}
