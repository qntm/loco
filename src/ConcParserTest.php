<?php declare(strict_types=1);
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;

final class ConcParserTest extends TestCase
{
    public function testConcParser()
    {
        $parser = new ConcParser(
            array(
                new RegexParser("#^a*#"),
                new RegexParser("#^b+#"),
                new RegexParser("#^c*#")
            )
        );

        $threw = false;
        try {
            $parser->match("", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("aaa", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($parser->match("b", 0), array("j" => 1, "value" => array("", "b", "")));
        $this->assertEquals($parser->match("aaab", 0), array("j" => 4, "value" => array("aaa", "b", "")));
        $this->assertEquals($parser->match("aaabb", 0), array("j" => 5, "value" => array("aaa", "bb", "")));
        $this->assertEquals($parser->match("aaabbbc", 0), array("j" => 7, "value" => array("aaa", "bbb", "c")));
    }
}
