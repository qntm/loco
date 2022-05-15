<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;
use Ferno\Loco\SimpleCommentGrammar;

final class SimpleCommentGrammarTest extends TestCase
{
    private static $simpleCommentGrammar;

    public static function setUpBeforeClass(): void
    {
        self::$simpleCommentGrammar = new SimpleCommentGrammar();
    }

    public function testSuccess(): void
    {
        $this->assertEquals(
            self::$simpleCommentGrammar->parse(
                "<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>   \r\n\t <p  >&lt;</p  >"
            ),
            "<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>   \r\n\t <p  >&lt;</p  >"
        );
    }

    public function testFailure(): void
    {
        foreach (array(
            "<h5 style=\"\">", // rogue "style" attribute
            "&",               // unescaped AMPERSAND
            "<",               // unescaped LESS_THAN
            "salkhsfg>",       // unescaped GREATER_THAN
            "</p",             // incomplete CLOSE_P
            "<br"              // incomplete FULL_BR
        ) as $string) {
            $threw = false;
            try {
                self::$simpleCommentGrammar->parse($string);
            } catch (ParseFailureException $e) {
                $threw = true;
            }
            $this->assertEquals($threw, true);
        }
    }
}
