<?php

namespace ferno\loco\test\parser;

use ferno\loco\GreedyMultiParser;
use ferno\loco\LazyAltParser;
use ferno\loco\ParseFailureException;
use ferno\loco\StringParser;
use PHPUnit_Framework_TestCase as TestCase;

class GreedyMultiParserTest extends TestCase
{
    public function testSuccess()
    {
        $parser = new GreedyMultiParser(new StringParser("a"), 0, null);

        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("", 0));
        $this->assertEquals(array("j" => 1, "value" => array("a")), $parser->match("a", 0));
        $this->assertEquals(array("j" => 2, "value" => array("a", "a")), $parser->match("aa", 0));
        $this->assertEquals(array("j" => 3, "value" => array("a", "a", "a")), $parser->match("aaa", 0));
    }

    public function testUpper()
    {
        $parser = new GreedyMultiParser(new StringParser("a"), 0, 1);
        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("", 0));
        $this->assertEquals(array("j" => 1, "value" => array("a")), $parser->match("a", 0));
    }

    public function testAmbiguousInnerParser()
    {
        $parser = new GreedyMultiParser(
            new LazyAltParser(
                array(
                    new StringParser("ab"),
                    new StringParser("a")
                )
            ),
            0,
            null
        );
        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("", 0));
        $this->assertEquals(array("j" => 1, "value" => array("a")), $parser->match("a", 0));
        $this->assertEquals(array("j" => 2, "value" => array("a", "a")), $parser->match("aa", 0));
        $this->assertEquals(array("j" => 2, "value" => array("ab")), $parser->match("ab", 0));
    }

    public function testAmbiguousRepeatedParser()
    {
        $parser = new GreedyMultiParser(
            new LazyAltParser(
                array(
                    new StringParser("aa"),
                    new StringParser("a")
                )
            ),
            0,
            null
        );
        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("", 0));
        $this->assertEquals(array("j" => 1, "value" => array("a")), $parser->match("a", 0));
        $this->assertEquals(array("j" => 2, "value" => array("aa")), $parser->match("aa", 0));
    }

    public function testUpperZero()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 0, 0);
        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("", 0));
        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("f", 0));
    }

    public function testUpperOne()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 0, 1);
        $this->assertEquals(array("j" => 0, "value" => array()), $parser->match("", 0));
        $this->assertEquals(array("j" => 1, "value" => array("f")), $parser->match("f", 0));
        $this->assertEquals(array("j" => 1, "value" => array("f")), $parser->match("ff", 0));
    }

    public function testOutOfBounds()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 1, 2);
        $this->setExpectedException(ParseFailureException::_CLASS);
        $parser->match("", 0);

    }

    public function testUpperTwo()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 1, 2);
        $this->assertEquals(array("j" => 1, "value" => array("f")), $parser->match("f", 0));
        $this->assertEquals(array("j" => 2, "value" => array("f", "f")), $parser->match("ff", 0));
        $this->assertEquals(array("j" => 2, "value" => array("f", "f")), $parser->match("fff", 0));

    }

    public function testOptionalEmptyMatch()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 1, null);
        $this->setExpectedException(ParseFailureException::_CLASS);

        $parser->match("", 0);
    }

    public function testOptionalSuccess()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 1, null);

        $this->assertEquals(array("j" => 1, "value" => array("f")), $parser->match("f", 0));
        $this->assertEquals(array("j" => 2, "value" => array("f", "f")), $parser->match("ff", 0));
        $this->assertEquals(array("j" => 3, "value" => array("f", "f", "f")), $parser->match("fff", 0));
        $this->assertEquals(array("j" => 2, "value" => array("f", "f")), $parser->match("ffg", 0));
    }
}
