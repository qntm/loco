<?php declare(strict_types=1);
namespace Ferno\Loco\examples;

use PHPUnit\Framework\TestCase;
use Ferno\Loco\ParseFailureException;

final class BnfGrammarTest extends TestCase
{
    private static $bnfGrammar;

    public static function setUpBeforeClass()
    {
        self::$bnfGrammar = new BnfGrammar();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testFullRuleSet()
    {
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

        $grammar2 = self::$bnfGrammar->parse($string);

        $grammar2->parse("Steve MacLaurin \n173 Acacia Avenue 7A\nStevenage, KY 33445\n");
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSelfReference()
    {
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

        $grammar3 = self::$bnfGrammar->parse($string);

        $grammar3->parse(" <a> ::= 'b' \n");
    }

    public function testFailure()
    {
        $threw = false;
        $string = " <incomplete ::=";
        try {
            self::$bnfGrammar->parse($string);
        } catch (ParseFailureException $e) {
            $threw = true;
        }
        $this->assertEquals($threw, true);
    }
}
