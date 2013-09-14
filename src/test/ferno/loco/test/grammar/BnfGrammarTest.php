<?php

namespace ferno\loco\test\parser\grammar;

use ferno\loco\grammar\BnfGrammar;
use ferno\loco\ParseFailureException;
use PHPUnit_Framework_TestCase as TestCase;

class BnfGrammarTest extends TestCase
{
    public function testSuburbMatcher()
    {
        // Full rule set
        $string = "
            <postal-address> ::= <name-part> <street-address> <zip-part>
            <name-part>      ::= <personal-part> <name-part> | <personal-part> <last-name> <opt-jr-part> <EOL>
            <personal-part>  ::= <initial> \".\" | <first-name>
            <street-address> ::= <house-num> <street-name> <opt-apt-num> <EOL>
            <zip-part>       ::= <town-name> \",\" <state-code> <ZIP-code> <EOL>
            <opt-jr-part>    ::= \"Sr.\" | \"Jr.\" | <roman-numeral> | \"\"

            <last-name>     ::= 'MacLaurin '
            <EOL>           ::= '\n'
            <initial>       ::= 'b'
            <first-name>    ::= 'Steve '
            <house-num>     ::= '173 '
            <street-name>   ::= 'Acacia Avenue '
            <opt-apt-num>   ::= '7A'
            <town-name>     ::= 'Stevenage'
            <state-code>    ::= ' KY '
            <ZIP-code>      ::= '33445'
            <roman-numeral> ::= 'g'
        ";

        $grammar = new BnfGrammar();
        $grammar2 = $grammar->parse($string);

        $expected = array(
            array(
                array(array('Steve ')),
                array('MacLaurin '),
                array(''),
                array("\n")
            ),
            array(
                array('173 '),
                array('Acacia Avenue '),
                array('7A'),
                array("\n")
            ),
            array(
                array('Stevenage'),
                ',',
                array(' KY '),
                array('33445'),
                array("\n")
            )
        );

        $result = $grammar2->parse("Steve MacLaurin \n173 Acacia Avenue 7A\nStevenage, KY 33445\n");
        $this->assertEquals($expected, $result);
    }

    public function testThing()
    {
        $string = "
            <syntax>         ::= <rule> | <rule> <syntax>
            <rule>           ::= <opt-whitespace> \"<\" <rule-name> \">\" <opt-whitespace> \"::=\" <opt-whitespace> <expression> <line-end>
            <opt-whitespace> ::= \" \" <opt-whitespace> | \"\"
            <expression>     ::= <list> | <list> \"|\" <expression>
            <line-end>       ::= <opt-whitespace> <EOL> <line-end> | <opt-whitespace> <EOL>
            <list>           ::= <term> | <term> <opt-whitespace> <list>
            <term>           ::= <literal> | \"<\" <rule-name> \">\"
            <literal>        ::= '\"' <text> '\"' | \"'\" <text> \"'\"

            <rule-name>      ::= 'a'
            <EOL>            ::= '\n'
            <text>           ::= 'b'
        ";

        $grammar = new BnfGrammar();
        $grammar3 = $grammar->parse($string);

        $expected = array(
            array(
                array(
                    ' ',
                    array('')
                ),
                '<',
                array('a'),
                '>',
                array(
                    ' ',
                    array('')
                ),
                '::=',
                array(
                    ' ',
                    array('')
                ),
                array(
                    array(
                        array(
                            array(
                                "'",
                                array('b'),
                                "'"
                            )
                        )
                    )
                ),
                array(
                    array(
                        ' ',
                        array('')
                    ),
                    array("\n")
                )
            )
        );

        $this->assertEquals($expected, $grammar3->parse(" <a> ::= 'b' \n"));

        // Should raise a ParseFailureException before trying to instantiate a Grammar
        $string = " <incomplete ::=";
        try {
            $grammar->parse($string);
            var_dump(false);
        } catch (ParseFailureException $e) {
        }

    }
}
