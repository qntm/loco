<?php

namespace ferno\loco\test\parser\grammar;

use ferno\loco\grammar\SimpleCommentGrammar;
use PHPUnit_Framework_TestCase as TestCase;

class SimpleCommentGrammarTest extends TestCase
{
    /** @var SimpleCommentGrammar */
    private $grammar;

    public function setUp()
    {
        $this->grammar = new SimpleCommentGrammar();
    }

    public function testSuccess()
    {
        $string = $this->grammar->parse("<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>   \r\n\t <p  >&lt;</p  >");
        $this->assertEquals("<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>   \r\n\t <p  >&lt;</p  >", $string);
    }

    public function failureProvider()
    {
        return array(
            array("<h5 style=\"\">"), // rogue "style" attribute
            array("&"), // unescaped AMPERSAND
            array("<"), // unescaped LESS_THAN
            array("salkhsfg>"), // unescaped GREATER_THAN
            array("</p"), // incomplete CLOSE_P
            array("<br") // incomplete FULL_BR
        );
    }

    /**
     * @dataProvider failureProvider
     * @param string $input
     */
    public function testFailures($input)
    {
        $this->setExpectedException('Exception');
        $this->grammar->parse($input);
    }
}
