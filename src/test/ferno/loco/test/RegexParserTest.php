<?php

namespace ferno\loco\test\parser;

use ferno\loco\GrammarException;
use ferno\loco\ParseFailureException;
use ferno\loco\RegexParser;
use PHPUnit_Framework_TestCase as TestCase;

class RegexParserTest extends TestCase
{
    public function testImproperAnchoring()
    {
        $this->setExpectedException(GrammarException::_CLASS);
        new RegexParser("#boo#");
    }

    public function testNonMatching()
    {
        $parser = new RegexParser("#^boo#");
        $this->setExpectedException(ParseFailureException::_CLASS);
        $parser->match("aboo", 0);
    }

    public function testMatching()
    {
        $parser = new RegexParser("#^boo#");

        $this->assertEquals(array("j" => 4, "value" => "boo"), $parser->match("aboo", 1));
    }

    public function testMatchingNumeric()
    {
        $parser = new RegexParser("#^-?(0|[1-9][0-9]*)(\\.[0-9]*)?([eE][-+]?[0-9]*)?#");
        $this->assertEquals(array("j" => 12, "value" => "4.444E-009"), $parser->match("-24.444E-009", 2));
    }
}
