<?php

namespace ferno\loco\test\parser;

use ferno\loco\ParseFailureException;
use ferno\loco\Utf8Parser;
use \PHPUnit_Framework_TestCase as TestCase;

class Utf8ParserTest extends TestCase
{
    /** @var Utf8Parser */
    private $parser;

    public function setUp()
    {
        $this->parser = new Utf8Parser();
    }

    public function testEmptyStringFails()
    {
        $this->setExpectedException(ParseFailureException::_CLASS);
        $this->parser->match('');
    }

    public function successfulConversions()
    {
        return array(
            array("\x41", array("j" => 1, "value" => "\x41")), # 1-byte character "A"
            array("\xC2\xAF", array("j" => 2, "value" => "\xC2\xAF")), # 2-byte character "¯"
            array("\xE2\x99\xA5", array("j" => 3, "value" => "\xE2\x99\xA5")), # 3-byte character "♥"
            array("\xF1\x8B\x81\x82", array("j" => 4, "value" => "\xF1\x8B\x81\x82")), # 4-byte character "񋁂"
            array("\xEF\xBB\xBF", array("j" => 3, "value" => "\xEF\xBB\xBF")), # "byte order mark"
            array("\xF0\x90\x80\x80", array("j" => 4, "value" => "\xF0\x90\x80\x80")), # 4-byte character
            array("\xF0\xA0\x80\x80", array("j" => 4, "value" => "\xF0\xA0\x80\x80")), # 4-byte character
            array("\x41\xC2\xAF\xE2\x99\xA5\xF1\x8B\x81\x82\xEF\xBB\xBF", array("j" => 1, "value" => "\x41")),
        );
    }

    /**
     * @dataProvider successfulConversions
     */
    public function testSuccessfulConversions($input, array $expected)
    {
        $this->assertEquals($expected, $this->parser->match($input, 0));
    }

    public function failingConversions()
    {
        return array(
            array("\xF4\x90\x80\x80"), # code point U+110000, out of range (max is U+10FFFF)
            array("\xC0\xA6"), # overlong encoding (code point is U+26; should be 1 byte, "\x26")
            array("\xC3\xFF"), # illegal continuation byte
            array("\xFF"), # illegal leading byte
            array("\xC2"), # mid-character termination
            array("\x00"), # null
            array("\xED\xA0\x80"), # 55296d
            array("\xED\xBF\xBF"), # 57343d
        );
    }

    /**
     * @dataProvider failingConversions
     */
    public function testFailingConversions($string)
    {
        $this->setExpectedException(ParseFailureException::_CLASS);
        $this->parser->match($string);
    }

    public function testCodePoints()
    {
        $this->assertEquals('A', Utf8Parser::getBytes(0x41));
        $this->assertEquals("\x26", Utf8Parser::getBytes(0x26));
        $this->assertEquals("\xC2\xAF", Utf8Parser::getBytes(0xAF)); # 2-byte character "¯"
        $this->assertEquals("\xE2\x99\xA5", Utf8Parser::getBytes(0x2665)); # 3-byte character "♥"
        $this->assertEquals("\xEF\xBB\xBF", Utf8Parser::getBytes(0xFEFF)); # "byte order mark"
        $this->assertEquals("\xF0\x90\x80\x80", Utf8Parser::getBytes(0x10000)); # 4-byte character
        $this->assertEquals("\xF0\xA0\x80\x80", Utf8Parser::getBytes(0x20000)); # 4-byte character
        $this->assertEquals("\xF1\x8B\x81\x82", Utf8Parser::getBytes(0x4B042)); # 4-byte character "񋁂"
    }
}
