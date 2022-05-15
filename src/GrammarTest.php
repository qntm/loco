<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;

final class GrammarTest extends TestCase
{
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
