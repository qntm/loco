<?php declare(strict_types=1);
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;

final class GreedyMultiParserTest extends TestCase
{
    public function testGreedyMultiParser()
    {
        $parser = new GreedyMultiParser(new StringParser("a"), 0, null);
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("a", 0), array("j" => 1, "value" => array("a")));
        $this->assertEquals($parser->match("aa", 0), array("j" => 2, "value" => array("a", "a")));
        $this->assertEquals($parser->match("aaa", 0), array("j" => 3, "value" => array("a", "a", "a")));
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
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("a", 0), array("j" => 1, "value" => array("a")));
        $this->assertEquals($parser->match("aa", 0), array("j" => 2, "value" => array("a", "a")));
        $this->assertEquals($parser->match("ab", 0), array("j" => 2, "value" => array("ab")));
    }

    public function test10D()
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
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("a", 0), array("j" => 1, "value" => array("a")));
        $this->assertEquals($parser->match("aa", 0), array("j" => 2, "value" => array("aa")));
    }

    public function test10E()
    {
        $parser = new GreedyMultiParser(new StringParser("a"), 0, 1);
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("a", 0), array("j" => 1, "value" => array("a")));
    }

    public function test10F()
    {
        $parser = new GreedyMultiParser(new StringParser("f"), 0, 0);
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("f", 0), array("j" => 0, "value" => array()));
        $parser = new GreedyMultiParser(new StringParser("f"), 0, 1);
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("f", 0), array("j" => 1, "value" => array("f")));
        $this->assertEquals($parser->match("ff", 0), array("j" => 1, "value" => array("f")));
        $parser = new GreedyMultiParser(new StringParser("f"), 1, 2);

        $threw = false;
        try {
            $parser->match("", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($parser->match("f", 0), array("j" => 1, "value" => array("f")));
        $this->assertEquals($parser->match("ff", 0), array("j" => 2, "value" => array("f", "f")));
        $this->assertEquals($parser->match("fff", 0), array("j" => 2, "value" => array("f", "f")));
        $parser = new GreedyMultiParser(new StringParser("f"), 1, null);

        $threw = false;
        try {
            $parser->match("", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($parser->match("f", 0), array("j" => 1, "value" => array("f")));
        $this->assertEquals($parser->match("ff", 0), array("j" => 2, "value" => array("f", "f")));
        $this->assertEquals($parser->match("fff", 0), array("j" => 3, "value" => array("f", "f", "f")));
        $this->assertEquals($parser->match("ffg", 0), array("j" => 2, "value" => array("f", "f")));
    }
}
