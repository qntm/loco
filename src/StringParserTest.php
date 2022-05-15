<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;

final class StringParserTest extends TestCase
{
    public function testStringParser(): void
    {
        $parser = new StringParser("needle");
        $this->assertEquals($parser->match("asdfneedle", 4), array("j" => 10, "value" => "needle"));

        $threw = false;
        try {
            $parser->match("asdfneedle", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }
}
