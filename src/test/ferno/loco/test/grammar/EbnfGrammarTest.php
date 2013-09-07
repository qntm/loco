<?php

namespace ferno\loco\test\parser\grammar;

use ferno\loco\grammar\EbnfGrammar;
use ferno\loco\ParseFailureException;
use PHPUnit_Framework_TestCase as TestCase;

class EbnfGrammarTest extends TestCase
{
    public function testSimple()
    {
        $string = "a = 'PROGRAM' ;";
        $grammar = new EbnfGrammar();
        $this->assertEquals(array('PROGRAM'), $grammar->parse($string)->parse("PROGRAM"));
    }

    public function testNoRules()
    {
        $string = "a = 'PROGRAM ;";
        $grammar = new EbnfGrammar();

        $this->setExpectedException(ParseFailureException::_CLASS);
        $grammar->parse($string);
    }

    public function testPascal()
    {
        // Full rule set
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

        $grammar = new EbnfGrammar();
        $pascalGrammar = $grammar->parse($string);

        $string =
            "PROGRAM DEMO1\n"
            . "BEGIN\n"
            . "  A0:=3;\n"
            . "  B:=45;\n"
            . "  H:=-100023;\n"
            . "  C:=A;\n"
            . "  D123:=B34A;\n"
            . "  BABOON:=GIRAFFE;\n"
            . "  TEXT:=\"Hello world!\";\n"
            . "END.";

        $expected = array(
            'PROGRAM',
            array(array(' ')),
            array(array('D'), array(array('E')), array(array('M')), array(array('O')), array(array('1'))), array(array("\n")),
            'BEGIN',
            array(array("\n"), array(' '), array(' ')),
            array(
                array(
                    array(array('A'), array(array('0'))),
                    ':=',
                    array(array(array('3'))),
                ),
                ';',
                array(array("\n"), array(' '), array(' ')),
            ),
            array(
                array(
                    array(array('B')),
                    ':=',
                    array(array(array('4'), array(array('5',)))),
                ),
                ';',
                array(array("\n"), array(' '), array(' ')),
            ),
            array(
                array(array(array('H')),
                    ':=',
                    array(array(array('-'), array('1'), array(array('0')), array(array('0')), array(array('0')), array(array('2')), array(array('3'))))),
                ';',
                array(array("\n"), array(' '), array(' ')),
            ),
            array(
                array(
                    array(array('C')),
                    ':=',
                    array(array(array('A')))),
                ';',
                array(array("\n"), array(' '), array(' ')),
            ),
            array(
                array(
                    array(array('D'), array(array('1')), array(array('2')), array(array('3'))),
                    ':=',
                    array(array(array('B'), array(array('3')), array(array('4')), array(array('A'))))
                ),
                ';',
                array(array("\n"), array(' '), array(' ')),
            ),
            array(
                array(
                    array(array('B'), array(array('A')), array(array('B')), array(array('O')), array(array('O')), array(array('N'))),
                    ':=',
                    array(array(array('G'), array(array('I')), array(array('R')), array(array('A')), array(array('F')), array(array('F')), array(array('E'))))
                ),
                ';',
                array(array("\n"), array(' '), array(' ')),
            ),
            array(
                array(
                    array(array('T'), array(array('E')), array(array('X')), array(array('T'))),
                    ':=',
                    array(array('"', array(array('H')), array(array('e')), array(array('l')), array(array('l')), array(array('o')), array(array(' ')), array(array('w')), array(array('o')), array(array('r')), array(array('l')), array(array('d')), array(array('!')), 13 => '"'))
                ),
                ';',
                array(array("\n"))
            ),
            'END.');
        $this->assertEquals($expected, $pascalGrammar->parse($string));
    }
}
