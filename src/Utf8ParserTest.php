<?php
namespace Ferno\Loco;

use PHPUnit\Framework\TestCase;
use Exception;

final class Utf8ParserTest extends TestCase
{
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
}
