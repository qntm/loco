<?php
// Takes a string presented in Extended Backus-Naur Form and turns it into a new Grammar
// object capable of recognising the language described by that string.
// http://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_Form

namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;
use Ferno\Loco\EbnfGrammar;

final class EbnfGrammarTest extends TestCase
{
    private static $ebnfGrammar;

    public static function setUpBeforeClass(): void
    {
        self::$ebnfGrammar = new EbnfGrammar();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testBasic(): void
    {
        $string = "a = 'PROGRAM' ;";
        self::$ebnfGrammar->parse($string)->parse("PROGRAM");
    }

    public function testFailure(): void
    {
        $string = "a = 'PROGRAM ;";
        $threw = false;
        try {
            self::$ebnfGrammar->parse($string);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testFullRuleSet(): void
    {
        $string = "
            (* a simple program syntax in EBNF - Wikipedia *)
            program = 'PROGRAM' , white space , identifier , white space ,
                      'BEGIN' , white space ,
                      { assignment , \";\" , white space } ,
                      'END.' ;
            identifier = alphabetic character , { alphabetic character | digit } ;
            number = [ \"-\" ] , digit , { digit } ;
            string = '\"' , { all characters } , '\"' ;
            assignment = identifier , \":=\" , ( number | identifier | string ) ;
            alphabetic character = \"A\" | \"B\" | \"C\" | \"D\" | \"E\" | \"F\" | \"G\"
                                 | \"H\" | \"I\" | \"J\" | \"K\" | \"L\" | \"M\" | \"N\"
                                 | \"O\" | \"P\" | \"Q\" | \"R\" | \"S\" | \"T\" | \"U\"
                                 | \"V\" | \"W\" | \"X\" | \"Y\" | \"Z\" ;
            digit = \"0\" | \"1\" | \"2\" | \"3\" | \"4\" | \"5\" | \"6\" | \"7\" | \"8\" | \"9\" ;
            white space = ( \" \" | \"\n\" ) , { \" \" | \"\n\" } ;
            all characters = \"H\" | \"e\" | \"l\" | \"o\" | \" \" | \"w\" | \"r\" | \"d\" | \"!\" ;
        ";
        $pascalGrammar = self::$ebnfGrammar->parse($string);

        $string =
             "PROGRAM DEMO1\n"
            ."BEGIN\n"
            ."  A0:=3;\n"
            ."  B:=45;\n"
            ."  H:=-100023;\n"
            ."  C:=A;\n"
            ."  D123:=B34A;\n"
            ."  BABOON:=GIRAFFE;\n"
            ."  TEXT:=\"Hello world!\";\n"
            ."END."
        ;
        $pascalGrammar->parse($string);
    }
}
