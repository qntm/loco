<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SimpleCommentGrammar.php';

final class SimpleCommentGrammarTest extends TestCase
{
    public function testSuccess(): void
    {
        global $simpleCommentGrammar;
        $this->assertEquals(
            $simpleCommentGrammar->parse("<h5>  Title<br /><em\n><strong\n></strong>&amp;</em></h5>   \r\n\t <p  >&lt;</p  >"),
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
                $simpleCommentGrammar->parse($string);
            } catch (Exception $e) {
                $threw = true;
            }
            $this->assertEquals($threw, true);
        }
    }
}
