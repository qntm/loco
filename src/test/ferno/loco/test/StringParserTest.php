<?php

namespace ferno\loco\test\parser;

use ferno\loco\ParseFailureException;
use ferno\loco\StringParser;
use PHPUnit_Framework_TestCase as TestCase;

class StringParserTest extends TestCase
{
    public function testMatchSuccess()
    {
        $parser = new StringParser("needle");
        $this->assertEquals(array("j" => 10, "value" => "needle"), $parser->match("asdfneedle", 4));
    }

    public function testMatchFailure()
    {
        $parser = new StringParser("needle");
        $this->setExpectedException(ParseFailureException::_CLASS);
        $this->assertEquals(0, $parser->match("asdfneedle"));
    }
}
