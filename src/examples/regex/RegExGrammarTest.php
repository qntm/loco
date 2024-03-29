<?php declare(strict_types=1);
namespace Ferno\Loco\examples\regex;

use PHPUnit\Framework\TestCase;

// apologies for the relative lack of exhaustive unit tests

final class RegExGrammarTest extends TestCase
{
    private static $regexGrammar;

    public static function setUpBeforeClass(): void
    {
        self::$regexGrammar = new RegExGrammar();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAll()
    {
        foreach (array(
            "a{2}",
            "a{2,}",
            "a{2,8}",
            "[$%\\^]{2,8}",
            "[ab]*",
            "([ab]*a)",
            "([ab]*a|[bc]*c)",
            "([ab]*a|[bc]*c)?",
            "([ab]*a|[bc]*c)?b*",
            "[a-zA-Z]",
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            "[a]",
            "[abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]",
            "[|(){},?*+\\[\\]\\^.\\\\]",
            "[\\f\\n\\r\\t\\v\\-]",
            "\\|",
            "\\(\\)\\{\\},\\?\\*\\+\\[\\]^.-\\f\\n\\r\\t\\v\\w\\d\\s\\W\\D\\S\\\\",
            "abcdef",
            "19\\d\\d-\\d\\d-\\d\\d",
            "[$%\\^]{2,}",
            "[$%\\^]{2}",
            ""
        ) as $string) {
            $pattern = self::$regexGrammar->parse($string);
        }
    }
}
