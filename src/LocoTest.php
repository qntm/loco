<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;
use Ferno\Loco;
use Exception;

final class LocoTest extends TestCase
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

    public function testImproperAnchoring(): void
    {
        $threw = false;
        try {
            $parser = new RegexParser("#boo#");
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testRegexParser(): void
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

    public function testUtf8Parser(): void
    {
        $parser = new Utf8Parser(array());

        $threw = false;
        try {
            $parser->match("", 0);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        # 1-byte character "A"
        $this->assertEquals($parser->match("\x41", 0), array("j" => 1, "value" => "\x41"));

        # 2-byte character "¯"
        $this->assertEquals($parser->match("\xC2\xAF", 0), array("j" => 2, "value" => "\xC2\xAF"));

        # 3-byte character "♥"
        $this->assertEquals($parser->match("\xE2\x99\xA5", 0), array("j" => 3, "value" => "\xE2\x99\xA5"));

        # 4-byte character "񋁂"
        $this->assertEquals($parser->match("\xF1\x8B\x81\x82", 0), array("j" => 4, "value" => "\xF1\x8B\x81\x82"));

        # "byte order mark" 11101111 10111011 10111111 (U+FEFF)
        $this->assertEquals($parser->match("\xEF\xBB\xBF", 0), array("j" => 3, "value" => "\xEF\xBB\xBF"));

        # 4-byte character
        $this->assertEquals($parser->match("\xF0\x90\x80\x80", 0), array("j" => 4, "value" => "\xF0\x90\x80\x80"));

        # 4-byte character
        $this->assertEquals($parser->match("\xF0\xA0\x80\x80", 0), array("j" => 4, "value" => "\xF0\xA0\x80\x80"));

        $this->assertEquals(
            $parser->match("\x41\xC2\xAF\xE2\x99\xA5\xF1\x8B\x81\x82\xEF\xBB\xBF", 0),
            array("j" => 1, "value" => "\x41")
        );

        $threw = false;
        try {
            $parser->match("\xF4\x90\x80\x80", 0); # code point U+110000, out of range (max is U+10FFFF)
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\xC0\xA6", 0); # overlong encoding (code point is U+26; should be 1 byte, "\x26")
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\xC3\xFF", 0); # illegal continuation byte
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\xFF", 0); # illegal leading byte
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\xC2", 0); # mid-character termination
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\x00", 0); # null
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\xED\xA0\x80", 0); # 55296d
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $parser->match("\xED\xBF\xBF", 0); # 57343d
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testGetBytes(): void
    {
        $this->assertEquals(Utf8Parser::getBytes(0x41), "A");
        $this->assertEquals(Utf8Parser::getBytes(0x26), "\x26");

        # 2-byte character "¯"
        $this->assertEquals(Utf8Parser::getBytes(0xAF), "\xC2\xAF");

        # 3-byte character "♥"
        $this->assertEquals(Utf8Parser::getBytes(0x2665), "\xE2\x99\xA5");

        # "byte order mark" 11101111 10111011 10111111 (U+FEFF)
        $this->assertEquals(Utf8Parser::getBytes(0xFEFF), "\xEF\xBB\xBF");

        # 4-byte character
        $this->assertEquals(Utf8Parser::getBytes(0x10000), "\xF0\x90\x80\x80");

        # 4-byte character
        $this->assertEquals(Utf8Parser::getBytes(0x20000), "\xF0\xA0\x80\x80");

        # 4-byte character "񋁂"
        $this->assertEquals(Utf8Parser::getBytes(0x4B042), "\xF1\x8B\x81\x82");

        # sure
        $this->assertEquals(Utf8Parser::getBytes(0xD800), "\xED\xA0\x80");

        $threw = false;
        try {
            # code point too large, cannot be encoded
            Utf8Parser::getBytes(0xFFFFFF);
        } catch (Exception $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

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

    public function testConcParser(): void
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

    public function testGreedyMultiParser(): void
    {
        $parser = new GreedyMultiParser(new StringParser("a"), 0, null);
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("a", 0), array("j" => 1, "value" => array("a")));
        $this->assertEquals($parser->match("aa", 0), array("j" => 2, "value" => array("a", "a")));
        $this->assertEquals($parser->match("aaa", 0), array("j" => 3, "value" => array("a", "a", "a")));
    }

    public function testAmbiguousInnerParser(): void
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

    public function test10D(): void
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

    public function test10E(): void
    {
        $parser = new GreedyMultiParser(new StringParser("a"), 0, 1);
        $this->assertEquals($parser->match("", 0), array("j" => 0, "value" => array()));
        $this->assertEquals($parser->match("a", 0), array("j" => 1, "value" => array("a")));
    }

    public function test10F(): void
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

    public function testRegularGrammar(): void
    {
        $grammar = new Grammar(
            "<A>",
            array(
                "<A>" => new EmptyParser()
            )
        );

        $threw = false;
        try {
            $grammar->parse("a");
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $this->assertEquals($grammar->parse(""), null);
    }

    public function testNullStars(): void
    {
        $threw = false;
        try {
            $grammar = new Grammar(
                "<S>",
                array(
                    "<S>" => new GreedyMultiParser("<A>", 7, null),
                    "<A>" => new EmptyParser()
                )
            );
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);

        $threw = false;
        try {
            $grammar = new Grammar(
                "<S>",
                array(
                    "<S>" => new GreedyStarParser("<A>"),
                    "<A>" => new GreedyStarParser("<B>"),
                    "<B>" => new EmptyParser()
                )
            );
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testMissingRootParser(): void
    {
        $threw = false;
        try {
            $grammar = new Grammar("<A>", array());
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testObviousLeftRecursion(): void
    {
        $threw = false;
        try {
            $grammar = new Grammar(
                "<S>",
                array(
                    "<S>" => new ConcParser(array("<S>"))
                )
            );
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testAdvancedLeftRecursion(): void
    {
        // only left-recursive because <B> is nullable
        $threw = false;
        try {
            $grammar = new Grammar(
                "<A>",
                array(
                    "<A>" => new LazyAltParser(
                        array(
                            new StringParser("Y"),
                            new ConcParser(
                                array("<B>", "<A>")
                            )
                        )
                    ),
                    "<B>" => new EmptyParser()
                )
            );
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    public function testEvenMoreComplex(): void
    {
        // This specifically checks for a bug in the original Loco left-recursion check).
        // This grammar is left-recursive in A -> B -> D -> A
        $threw = false;
        try {
            $grammar = new Grammar(
                "<A>",
                array(
                    "<A>" => new ConcParser(array("<B>")),
                    "<B>" => new LazyAltParser(array("<C>", "<D>")),
                    "<C>" => new ConcParser(array(new StringParser("C"))),
                    "<D>" => new LazyAltParser(array("<C>", "<A>"))
                )
            );
        } catch (GrammarException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }
}
