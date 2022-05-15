<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/BnfGrammar.php';

final class BnfGrammarTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testFullRuleSet(): void
    {
        global $bnfGrammar;
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

        $grammar2 = $bnfGrammar->parse($string);

        $grammar2->parse("Steve MacLaurin \n173 Acacia Avenue 7A\nStevenage, KY 33445\n");
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSelfReference(): void
    {
        global $bnfGrammar;
        $string = "
            <syntax>     ::= <rule> | <rule> <syntax>
            <rule>       ::= <opt-ws> \"<\" <rule-name> \">\" <opt-ws> \"::=\" <opt-ws> <expression> <line-end>
            <opt-ws>     ::= \" \" <opt-ws> | \"\"
            <expression> ::= <list> | <list> \"|\" <expression>
            <line-end>   ::= <opt-ws> <EOL> <line-end> | <opt-ws> <EOL>
            <list>       ::= <term> | <term> <opt-ws> <list>
            <term>       ::= <literal> | \"<\" <rule-name> \">\"
            <literal>    ::= '\"' <text> '\"' | \"'\" <text> \"'\"

            <rule-name>  ::= 'a'
            <EOL>        ::= '\n'
            <text>       ::= 'b'
        ";

        $grammar3 = $bnfGrammar->parse($string);

        $grammar3->parse(" <a> ::= 'b' \n");
    }

    public function testFailure(): void
    {
        global $bnfGrammar;
        $threw = false;
        $string = " <incomplete ::=";
        try {
            $bnfGrammar->parse($string);
        } catch (Ferno\Loco\ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }
}
