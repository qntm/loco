<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/WirthGrammar.php';

final class WirthGrammarTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testWith(): void
    {
        global $wirthGrammar;
        // This is the syntax for Wirth syntax notation except it lacks whitespace
        $string = "
            SYNTAX     = { PRODUCTION } .
            PRODUCTION = IDENTIFIER \"=\" EXPRESSION \".\" .
            EXPRESSION = TERM { \"|\" TERM } .
            TERM       = FACTOR { FACTOR } .
            FACTOR     = IDENTIFIER
                                 | LITERAL
                                 | \"[\" EXPRESSION \"]\"
                                 | \"(\" EXPRESSION \")\"
                                 | \"{\" EXPRESSION \"}\" .
            IDENTIFIER = letter { letter } .
            LITERAL    = \"\"\"\" character { character } \"\"\"\" .
            digit      = \"0\" | \"1\" | \"2\" | \"3\" | \"4\" | \"5\" | \"6\" | \"7\" | \"8\" | \"9\" .
            upper      = \"A\" | \"B\" | \"C\" | \"D\" | \"E\" | \"F\" | \"G\" | \"H\" | \"I\" | \"J\"
                       | \"K\" | \"L\" | \"M\" | \"N\" | \"O\" | \"P\" | \"Q\" | \"R\" | \"S\" | \"T\"
                       | \"U\" | \"V\" | \"W\" | \"X\" | \"Y\" | \"Z\" .
            lower      = \"a\" | \"b\" | \"c\" | \"d\" | \"e\" | \"f\" | \"g\" | \"h\" | \"i\" | \"j\"
                       | \"k\" | \"l\" | \"m\" | \"n\" | \"o\" | \"p\" | \"q\" | \"r\" | \"s\" | \"t\"
                       | \"u\" | \"v\" | \"w\" | \"x\" | \"y\" | \"z\" .
            letter     = upper | lower .
            character  = letter | digit | \"=\" | \".\" | \"\"\"\"\"\" .
        ";
        $wirthGrammar->parse($string)->parse("SYNTAX={PRODUCTION}.");
    }
}