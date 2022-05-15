<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;

final class LazyAltParserTest extends TestCase
{
    public function testLazyAltParser1(): void
    {
        $parser = new LazyAltParser(
            array(
                new StringParser("abc"),
                new StringParser("ab"),
                new StringParser("a")
            )
        );

        $threw = false;
        try {
            $parser->match("0", 1);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($parser->match("0a", 1), array("j" => 2, "value" => "a"));
        $this->assertEquals($parser->match("0ab", 1), array("j" => 3, "value" => "ab"));
        $this->assertEquals($parser->match("0abc", 1), array("j" => 4, "value" => "abc"));
        $this->assertEquals($parser->match("0abcd", 1), array("j" => 4, "value" => "abc"));
    }

    public function testLazyAltParser2(): void
    {
        $threw = false;
        try {
            new LazyAltParser(array());
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }
}
