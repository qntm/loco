<?php declare(strict_types=1);
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;

final class RegexParserTest extends TestCase
{
    public function testImproperAnchoring()
    {
        $threw = false;
        try {
            $parser = new RegexParser("#boo#");
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testRegexParser()
    {
        $parser = new RegexParser("#^boo#");
        $this->assertEquals($parser->match("boo", 0), array("j" => 3, "value" => "boo"));

        $threw = false;
        try {
            $parser->match("aboo", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($parser->match("aboo", 1), array("j" => 4, "value" => "boo"));
        $parser = new RegexParser("#^-?(0|[1-9][0-9]*)(\.[0-9]*)?([eE][-+]?[0-9]*)?#");
        $this->assertEquals($parser->match("-24.444E-009", 2), array("j" => 12, "value" => "4.444E-009"));
    }
}
