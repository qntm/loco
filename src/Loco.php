<?php
namespace Ferno\Loco;

use Exception;

// This occurs at Grammar instantiation time, e.g. left-recursion, null-stars,
// miscellaneous housekeeping errors
class GrammarException extends Exception
{
}

// Occurs when any parser fails to parse what it's supposed to
// parse. Usually non-fatal and almost always caught
class ParseFailureException extends Exception
{
    public function __construct($message, $i, $string, $code = 0, Exception $previous = null)
    {
        $message .= " at position " . var_export($i, true) . " in string " . var_export($string, true);
        parent::__construct($message, $code);
    }
}

// a helpful internal function
function serialiseArray($array)
{
    $string = "array(";
    foreach (array_keys($array) as $keyId => $key) {
        $string .= var_export($key, true) . " => ";
        if (is_string($array[$key])) {
            $string .= var_export($array[$key], true);
        } else {
            $string .= $array[$key]->__toString();
        }

        if ($keyId + 1 !== count($array)) {
            $string .= ", ";
        }
    }
    $string .= ")";
    return $string;
}

// http://en.wikipedia.org/wiki/Parser_combinator
// These parsers are all unusual in that instead of returning a complete
// set of js and tokens, each returns either a single successful combination of
// j and result, or throws a ParseFailureException. These are, then, "monoparsers"
abstract class MonoParser
{

    // A string form for any parser should be generated at instantiation time.
    // This string should be *approximately* the "new MonoParser()" syntax,
    // although stringifying the callback is problematic so don't bother trying.
    // serialiseArray() helps with array arguments (var_export is no good because
    // it leaves line breaks!)
    protected $string;

    public function __toString()
    {
        return $this->string;
    }

    // An array of internal parsers, which are called recursively by and hence
    // "exist inside of" this parser. These may be actual MonoParser
    // objects.
    // They may also be references to (i.e. string names of) other parsers
    // elsewhere within the Grammar object within which $this presumably exists.
    // The Grammar object will resolve() these strings into references
    // to the real parsers at Grammar instantiation time.
    // This list is empty for "static" parsers
    public $internals;

    // A function to apply to the result of whatever this parser just parsed.
    // The arguments supplied to this callback depend on the parser class;
    // check!
    public $callback;

    abstract public function defaultCallback();

    public function __construct($internals, $callback)
    {
        if (!is_string($this->string)) {
            throw new Exception("You need to populate \$string");
        }

        // Perform basic validation.
        if (!is_array($internals)) {
            throw new GrammarException(var_export($internals, true) . " should be an array");
        }
        foreach ($internals as $internal) {
            if (!is_string($internal) && !($internal instanceof MonoParser)) {
                throw new GrammarException(var_export($internal, true) . " should be either a string or a MonoParser");
            }
        }
        $this->internals = $internals;

        // if null, set default callback
        if ($callback === null) {
            $callback = array($this, "defaultCallback");
        }
        if (!is_callable($callback)) {
            throw new GrammarException("Callback should be a callable function");
        }
        $this->callback = $callback;
    }

    // try to match this parser at the specified point.
    // returns j and args to pass to the callback, or throws exception on failure
    abstract public function getResult($string, $i = 0);

    // apply callback to returned value before returning it
    public function match($string, $i = 0)
    {
        $result = $this->getResult($string, $i);
        return array(
            "j" => $result["j"],
            "value" => call_user_func_array($this->callback, $result["args"])
        );
    }

    // Parse: try to match this parser at the beginning of the string
    // Return the result only on success, or throw exception on failure
    // or if the match doesn't encompass the whole string
    public function parse($string)
    {
        $result = $this->getResult($string, 0);
        if ($result["j"] != strlen($string)) {
            throw new ParseFailureException("Parsing completed prematurely", $result["j"], $string);
        }

        // notice how this isn't called until AFTER we've verified that
        // the whole thing has been parsed
        return call_user_func_array($this->callback, $result["args"]);
    }

    // Every parser assumes that it is non-nullable from the outset
    public $nullable = false;

    // Evaluate the nullability of this parser with respect to each of its
    // internals. This function must NOT simply "return $nullable;", whose content
    // may be out of date; this function must NOT modify $nullable, either, because
    // that is not for this function to do; this function must NOT recursively
    // call evaluateNullability() on any of its internals because that could easily
    // result in a stack overflow.
    // Just gets $nullable for each internal, if any.
    // This has to be called after all strings have been resolved to parser references.
    abstract public function evaluateNullability();

    // The immediate first-set of a parser is the set of all internal parsers
    // which could be matched first. For example, if A = B . C then the first-set
    // of A is usually {B}. If B is nullable, then C could also be matched first, so the
    // first-set is {B, C}.
    // This has to be called after the "nullability flood fill" is complete,
    // or "Called method of non-object" exceptions will arise
    abstract public function firstSet();
}

// Static parsers contain no internal parsers.
abstract class StaticParser extends MonoParser
{

    public function __construct($callback)
    {
        parent::__construct(array(), $callback);
    }

    // no internals => empty immediate first-set
    public function firstSet()
    {
        return array();
    }

    // empty immediate first-set => empty extended first-set
    // empty extended first-set => extended first-set cannot contain self
    // extended first-set does not contain self => not left-recursive
}

// Match the empty string
class EmptyParser extends StaticParser
{
    public function __construct($callback = null)
    {
        $this->string = "new " . get_class() . "()";
        parent::__construct($callback);
    }

    // default callback returns null
    public function defaultCallback()
    {
        return null;
    }

    // Always match successfully, pass no args to callback
    public function getResult($string, $i = 0)
    {
        return array(
            "j" => $i,
            "args" => array()
        );
    }

    // emptyparser is nullable.
    public function evaluateNullability()
    {
        return true;
    }
}

// Match a static string.
// Callback should accept a single argument which is the static string in question.
class StringParser extends StaticParser
{
    private $needle;

    public function __construct($needle, $callback = null)
    {
        if (!is_string($needle)) {
            throw new GrammarException("Can't create a " . get_class() . " with 'string' " . var_export($needle, true));
        }
        $this->needle = $needle;
        $this->string = "new " . get_class() . "(" . var_export($needle, true) . ")";
        parent::__construct($callback);
    }

    // default callback: just return the string that was matched
    public function defaultCallback()
    {
        return func_get_arg(0);
    }

    public function getResult($string, $i = 0)
    {
        if (strpos($string, $this->needle, $i) === $i) {
            return array(
                "j" => $i + strlen($this->needle),
                "args" => array($this->needle)
            );
        }
        throw new ParseFailureException($this . " could not find string " . var_export($this->needle, true), $i, $string);
    }

    // nullable only if string is ""
    public function evaluateNullability()
    {
        return ($this->needle === "");
    }
}

// Parser uses a regex to match itself. Regexes are time-consuming to execute,
// so use StringParser to match static strings where possible.
// Regexes can match multiple times in theory, but this pattern returns a singleton
// Callback should accept an array of all the matches made
class RegexParser extends StaticParser
{
    private $pattern;

    public function __construct($pattern, $callback = null)
    {
        $this->string = "new " . get_class() . "(" . var_export($pattern, true) . ")";
        if (substr($pattern, 1, 1) !== "^") {
            throw new GrammarException($this . " doesn't anchor at the beginning of the string!");
        }
        $this->pattern = $pattern;
        parent::__construct($callback);
    }

    // default callback: return only the main match
    public function defaultCallback()
    {
        return func_get_arg(0);
    }

    public function getResult($string, $i = 0)
    {
        if (preg_match($this->pattern, substr($string, $i), $matches) === 1) {
            return array(
                "j" => $i + strlen($matches[0]),
                "args" => $matches
            );
        }
        throw new ParseFailureException($this . " could not match expression " . var_export($this->pattern, true), $i, $string);
    }

    // nullable only if regex matches ""
    public function evaluateNullability()
    {
        return (preg_match($this->pattern, "", $matches) === 1);
    }
}

// UTF-8 parser parses one valid UTF-8 character and returns the
// resulting code point.
// Callback should accept the character (in the form of bytes)
class Utf8Parser extends StaticParser
{

    # Some basic useful information about each possible byte
    # sequence i.e. prefix and number of free bits
    # binary expressions for extracting useful information
    # Pre-calculated. Could be calculated on the fly but nobody caaares
    private static $expressions = array(
        array(
            "numbytes" => 1,
            "freebits" => array(7), # 0xxxxxxx
            "mask" => "\x80",    # 10000000
            "result" => "\x00",    # 00000000
            "extract" => "\x7F",    # 01111111
            "mincodepoint" => 0,
            "maxcodepoint" => 127
        ),
        array(
            "numbytes" => 2,
            "freebits" => array(5, 6), # 110xxxxx 10xxxxxx
            "mask" => "\xE0\xC0",   # 11100000 11000000
            "result" => "\xC0\x80",   # 11000000 10000000
            "extract" => "\x1F\x3F",   # 00011111 00111111
            "mincodepoint" => 128,
            "maxcodepoint" => 2047
        ),
        array(
            "numbytes" => 3,
            "freebits" => array(4, 6, 6), # 1110xxxx 10xxxxxx 10xxxxxx
            "mask" => "\xF0\xC0\xC0",  # 11110000 11000000 11000000
            "result" => "\xE0\x80\x80",  # 11100000 10000000 10000000
            "extract" => "\x0F\x3F\x3F",  # 00001111 00111111 00111111
            "mincodepoint" => 2048,
            "maxcodepoint" => 65535
        ),
        array(
            "numbytes" => 4,
            "freebits" => array(3, 6, 6, 6), # 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
            "mask" => "\xF8\xC0\xC0\xC0", # 11111000 11000000 11000000 11000000
            "result" => "\xF0\x80\x80\x80", # 11110000 10000000 10000000 10000000
            "extract" => "\x07\x3F\x3F\x3F", # 00000111 00111111 00111111 00111111
            "mincodepoint" => 65536,
            "maxcodepoint" => 2097151
        )
    );

    // Unicode control characters
    // http://www.fileformat.info/info/unicode/category/Cc/list.htm
    public static $controls = array(
        0x0000, 0x0001, 0x0002, 0x0003, 0x0004, 0x0005, 0x0006, 0x0007,
        0x0008, 0x0009, 0x000A, 0x000B, 0x000C, 0x000D, 0x000E, 0x000F,
        0x0010, 0x0011, 0x0012, 0x0013, 0x0014, 0x0015, 0x0016, 0x0017,
        0x0018, 0x0019, 0x001A, 0x001B, 0x001C, 0x001D, 0x001E, 0x001F,
        0x007F, 0x0080, 0x0081, 0x0082, 0x0083, 0x0084, 0x0085, 0x0086,
        0x0087, 0x0088, 0x0089, 0x008A, 0x008B, 0x008C, 0x008D, 0x008E,
        0x008F, 0x0090, 0x0091, 0x0092, 0x0093, 0x0094, 0x0095, 0x0096,
        0x0097, 0x0098, 0x0099, 0x009A, 0x009B, 0x009C, 0x009D, 0x009E,
        0x009F
    );

    // http://en.wikipedia.org/wiki/Valid_characters_in_XML#Non-restricted_characters
    private static $xmlSafeRanges = array(
        // The only C0 controls acceptable in XML 1.0 and 1.1
        array("bottom" => 0x0009, "top" => 0x000A),
        array("bottom" => 0x000D, "top" => 0x000D),

        // Non-control characters in the Basic Latin block, excluding the last C0 control
        array("bottom" => 0x0020, "top" => 0x007E),

        // The only C1 control character accepted in both XML 1.0 and XML 1.1
        array("bottom" => 0x0085, "top" => 0x0085),

        // Rest of BMP, excluding all non-characters (such as surrogates)
        array("bottom" => 0x00A0, "top" => 0xD7FF),
        array("bottom" => 0xE000, "top" => 0xFDCF),
        array("bottom" => 0xFDE0, "top" => 0xFFFD),

        // Exclude all non-characters in supplementary planes
        array("bottom" => 0x10000, "top" => 0x1FFFD),
        array("bottom" => 0x20000, "top" => 0x2FFFD),
        array("bottom" => 0x30000, "top" => 0x3FFFD),
        array("bottom" => 0x40000, "top" => 0x4FFFD),
        array("bottom" => 0x50000, "top" => 0x5FFFD),
        array("bottom" => 0x60000, "top" => 0x6FFFD),
        array("bottom" => 0x70000, "top" => 0x7FFFD),
        array("bottom" => 0x80000, "top" => 0x8FFFD),
        array("bottom" => 0x90000, "top" => 0x9FFFD),
        array("bottom" => 0xA0000, "top" => 0xAFFFD),
        array("bottom" => 0xB0000, "top" => 0xBFFFD),
        array("bottom" => 0xC0000, "top" => 0xCFFFD),
        array("bottom" => 0xD0000, "top" => 0xDFFFD),
        array("bottom" => 0xE0000, "top" => 0xEFFFD),
        array("bottom" => 0xF0000, "top" => 0xFFFFD),
        array("bottom" => 0x100000, "top" => 0x10FFFD)
    );

    # should contain a blacklist of CHARACTERS (i.e. strings), not code points
    private $blacklist;

    public function __construct($blacklist = array(), $callback = null)
    {
        $this->blacklist = $blacklist;
        $this->string = "new " . get_class() . "(" . serialiseArray($blacklist) . ")";
        parent::__construct($callback);
    }

    // default callback: just return the string that was matched
    public function defaultCallback()
    {
        return func_get_arg(0);
    }

    public function getResult($string, $i = 0)
    {
        foreach (self::$expressions as $expression) {
            $length = $expression["numbytes"];

            // string is too short to accommodate this expression
            // try next expression
            // (since expressions are in increasing order of size, this is pointless)
            if (strlen($string) < $i + $length) {
                continue;
            }

            $character = substr($string, $i, $length);

            // string doesn't match expression: try next expression
            if (($character & $expression["mask"]) !== $expression["result"]) {
                continue;
            }

            // Character is blacklisted: abandon effort entirely
            if (in_array($character, $this->blacklist)) {
                break;
            }

            // get code point
            $codepoint = 0;
            foreach ($expression["freebits"] as $byteId => $freebits) {
                $codepoint <<= $freebits;
                $codepoint += ord($string[$i + $byteId] & $expression["extract"][$byteId]);
            }

            // overlong encoding: not valid UTF-8, abandon effort entirely
            if ($codepoint < $expression["mincodepoint"]) {
                break;
            }

            // make sure code point falls inside a safe range
            foreach (self::$xmlSafeRanges as $range) {
                // code point isn't in range: try next range
                if ($codepoint < $range["bottom"] || $range["top"] < $codepoint) {
                    continue;
                }

                // code point is in a safe range.
                // OK: return
                return array(
                    "j" => $i + $length,
                    "args" => array($character)
                );
            }

            // code point isn't safe: abandon effort entirely
            break;
        }

        throw new ParseFailureException($this . " could not find a UTF-8 character", $i, $string);
    }

    // UTF-8 parser is not nullable.
    public function evaluateNullability()
    {
        return false;
    }

    // convert a Unicode code point into UTF-8 bytes
    public static function getBytes($codepoint)
    {
        foreach (self::$expressions as $expression) {
            // next expression
            if ($codepoint > $expression["maxcodepoint"]) {
                continue;
            }

            // pull out basic numbers
            $string = "";
            foreach (array_reverse($expression["freebits"]) as $freebits) {
                $x = $codepoint & ((1 << $freebits) - 1);
                $string = chr($x) . $string;
                $codepoint >>= $freebits;
            }

            // add "cladding"
            $string |= $expression["result"];
            return $string;
        }
    }
}

// Takes the input parsers and applies them all in turn. "Lazy" indicates
// that as soon as a single parser matches, those matches are returned and
// processing halts.
// This is best used when the input parsers are mutually exclusive
// callback should accept a single argument which is the single match
// LazyAltParsers become risky when one is a proper prefix of another
class LazyAltParser extends MonoParser
{
    public function __construct($internals, $callback = null)
    {
        if (count($internals) === 0) {
            throw new GrammarException("Can't make a " . get_class() . " without at least one internal parser.\n");
        }
        $this->internals = $internals;
        $this->string = "new " . get_class() . "(" . serialiseArray($internals) . ")";
        parent::__construct($internals, $callback);
    }

    // default callback: return the sole result unmodified
    public function defaultCallback()
    {
        return func_get_arg(0);
    }

    public function getResult($string, $i = 0)
    {
        foreach ($this->internals as $internal) {
            try {
                $match = $internal->match($string, $i);
            } catch (ParseFailureException $e) {
                continue;
            }
            return array(
                "j" => $match["j"],
                "args" => array($match["value"])
            );
        }
        throw new ParseFailureException($this . " could not match another token", $i, $string);
    }

    // Nullable if any internal is nullable.
    public function evaluateNullability()
    {
        foreach ($this->internals as $internal) {
            if ($internal->nullable) {
                return true;
            }
        }
        return false;
    }

    // every internal is potentially a first.
    public function firstSet()
    {
        return $this->internals;
    }
}

// Callback accepts a single argument containing all submatches, however many
class GreedyMultiParser extends MonoParser
{
    private $lower;
    public $optional;

    public function __construct($internal, $lower, $upper, $callback = null)
    {
        $this->lower = $lower;
        if (is_null($upper)) {
            $this->optional = null;
        } else {
            if ($upper < $lower) {
                throw new GrammarException("Can't create a " . get_class() . " with lower limit " . var_export($lower, true)
                    . " and upper limit " . var_export($upper, true));
            }
            $this->optional = $upper - $lower;
        }
        $this->string = "new " . get_class() . "(" . $internal . ", " . var_export($lower, true) . ", " . var_export($upper, true)
            . ")";
        parent::__construct(array($internal), $callback);
    }

    // default callback: just return the list
    public function defaultCallback()
    {
        return func_get_args();
    }

    public function getResult($string, $i = 0)
    {

        $result = array("j" => $i, "args" => array());

        // First do the non-optional segment
        // Any parse failures here are terminal
        for ($k = 0; $k < $this->lower; $k++) {
            $match = $this->internals[0]->match($string, $result["j"]);
            $result["j"] = $match["j"];
            $result["args"][] = $match["value"];
        }

        // next, the optional segment
        // null => no upper limit
        for ($k = 0; $this->optional === null || $k < $this->optional; $k++) {
            try {
                $match = $this->internals[0]->match($string, $result["j"]);
                $result["j"] = $match["j"];
                $result["args"][] = $match["value"];
            } catch (ParseFailureException $e) {
                break;
            }
        }
        return $result;
    }

    // nullable if lower limit is zero OR internal is nullable.
    public function evaluateNullability()
    {
        return ($this->lower == 0 || $this->internals[0]->nullable === true);
    }

    // This parser contains only one internal
    public function firstSet()
    {
        return array($this->internals[0]);
    }
}

// Tiny subclass is ironically much more useful than GreedyMultiParser
class GreedyStarParser extends GreedyMultiParser
{
    public function __construct($internal, $callback = null)
    {
        $this->string = "new " . get_class() . "(" . $internal . ")";
        parent::__construct($internal, 0, null, $callback);
    }
}

// Match several things in a row. Callback should accept one argument
// for each parser listed.
class ConcParser extends MonoParser
{
    public function __construct($internals, $callback = null)
    {
        $this->string = "new " . get_class() . "(" . serialiseArray($internals) . ")";
        parent::__construct($internals, $callback);
    }

    // Default callback (this should be used rarely) returns all arguments as
    // an array. In the majority of cases the user should specify a callback.
    public function defaultCallback()
    {
        return func_get_args();
    }

    public function getResult($string, $i = 0)
    {
        $j = $i;
        $args = array();
        foreach ($this->internals as $parser) {
            $match = $parser->match($string, $j);
            $j = $match["j"];
            $args[] = $match["value"];
        }
        return array("j" => $j, "args" => $args);
    }

    // First-set is built up as follows...
    public function firstSet()
    {
        $firstSet = array();
        foreach ($this->internals as $internal) {
            // The first $internal is always in the first-set
            $firstSet[] = $internal;

            // If $internal was nullable, then the next internal in the
            // list is also in the first-set, so continue the loop.
            // Otherwise we are done.
            if (!$internal->nullable) {
                break;
            }
        }
        return $firstSet;
    }

    // only nullable if everything in the list is nullable
    public function evaluateNullability()
    {
        foreach ($this->internals as $internal) {
            if (!$internal->nullable) {
                return false;
            }
        }
        return true;
    }
}

// Grammar is a container for a bunch of parsers. This container is
// necessary so that the parser names used in the constructions of each
// parser can actually refer to other parsers instead of just being
// useless strings.
class Grammar extends MonoParser
{

    // All parsing begins with the parser of this name.
    // $S should not be an actual parser
    private $S;

    public function __construct($S, $internals, $callback = null)
    {
        $this->string = "new " . get_class() . "(" . var_export($S, true) . ", " . serialiseArray($internals) . ")";
        parent::__construct($internals, $callback);

        if (!array_key_exists($S, $this->internals)) {
            throw new GrammarException("This grammar begins with rule '" . var_export($S, true)
                . "' but no parser with this name was given.");
        }
        $this->S = $S;

        // Each parser may have internal sub-parsers to which it
        // "farms out" parsing duties. (This is contained in each parser's internal
        // list, $internals). In some cases, these will appear as
        // full-blown internal parsers, which is fine, but in other cases
        // (unavoidably) these will appear as mere strings, intended to refer
        // to other parsers elsewhere in the complete grammar.

        // Strings alone are no good for parsing purposes, so at this stage,
        // we resolve this by replacing each such string with a
        // reference to "the real thing" - if it can be found.

        // this needs to recurse over all inner parsers!!
        $this->resolve($this);

        // Nullability.
        // It is impossible to be certain whether an arbitrary parser is nullable
        // without knowing the nullability status of its internal parsers.
        // Because this chain may recurse, the nullability of a general collection
        // of parsers has to be evaluated by "bubbling up" nullability states
        // until we are certain that all nullable parsers have been marked as such.
        // It is not unlike a "flood fill" procedure.
        while (1) {
            foreach ($this->internals as $internal) {
                if ($internal->nullable === true) {
                    continue;
                }

                if (!$internal->evaluateNullability()) {
                    continue;
                }

                // If we get here, then $internal is marked as non-nullable, but
                // has been newly evaluated as nullable. A change has occurred! So,
                // mark $internal as nullable now and start the process over again.
                $internal->nullable = true;
                continue(2);
            }

            // If we reach this point then we are done marking more internals as
            // nullable. The nullability fill is complete
            break;
        }

        // The reason for needing to know nullability is so that we can confidently
        // create the immediate first-set of each parser.

        // This allows the creation of the extended first-set of each parser.

        // This in turn is necessary to detect left recursion, which occurs
        // if and only if a parser contains ITSELF in its own extended first-set.
        foreach ($this->internals as $internal) {
            // Find the extended first-set of this parser. If this parser is
            // contained in its own first-set, then it is left-recursive.
            // This has to be called after the "nullability flood fill" is complete.
            $firstSet = array($internal);
            $i = 0;
            while ($i < count($firstSet)) {
                $current = $firstSet[$i];
                foreach ($current->firstSet() as $next) {
                    // Left-recursion
                    if ($next === $internal) {
                        throw new GrammarException("This grammar is left-recursive in " . $internal . ".");
                    }

                    // If it's already in the list, then skip it
                    // this DOESN'T imply left-recursion, though
                    for ($j = 0; $j < count($firstSet); $j++) {
                        if ($next === $firstSet[$j]) {
                            continue(2);
                        }
                    }

                    $firstSet[] = $next;
                }
                $i++;
            }
        }

        // Nullability is also required for this step:
        // If a GreedyMultiParser's inner parser is capable of matching a
        // string of zero length, and it has an unbounded upper limit, then
        // it is going to loop forever.
        // In this situation, we raise a very serious error
        foreach ($this->internals as $internal) {
            if (!is_a($internal, "GreedyMultiParser")) {
                continue;
            }
            if ($internal->optional !== null) {
                continue;
            }

            if ($internal->internals[0]->nullable) {
                throw new GrammarException($internal . " has internal parser " . $internal->internals[0]
                    . ", which matches the empty string. This will cause infinite loops when parsing.");
            }
        }
    }

    // Look at all of the $internals of the supplied parser, and observe the
    // ones which are strings instead of being full-blown parsers. For each
    // string, find the actual parser here in the grammar which has that name.
    // Then, replace the string with a reference to that parser.
    // The result is that the $parser's $internals are now all (references to)
    // real parsers, no longer strings.
    // Be cautious modifying this code, it was constructed quite delicately to
    // avoid infinite loops
    private function resolve($parser)
    {

        $keys = array_keys($parser->internals);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];

            // replace names with references
            if (is_string($parser->internals[$key])) {
                // make sure the other parser that we're about to create a reference to actually exists
                $name = $parser->internals[$key];
                if (!array_key_exists($name, $this->internals)) {
                    throw new GrammarException($parser . " contains a reference to another parser " . var_export($name, true)
                        . " which cannot be found");
                }

                // create that reference
                $parser->internals[$key] =& $this->internals[$name];
            } else {
                // already a parser? No need to replace it!
                // but we do need to recurse!
                $parser->internals[$key] = $this->resolve($parser->internals[$key]);
            }
        }
        return $parser;
    }

    // default callback (this should be rarely modified) returns
    // first argument only
    public function defaultCallback()
    {
        return func_get_arg(0);
    }

    // use the "main" internal parser, S
    public function getResult($string, $i = 0)
    {
        $match = $this->internals[$this->S]->match($string, $i);
        return array(
            "j" => $match["j"],
            "args" => array($match["value"])
        );
    }

    // nullable iff <S> is nullable
    public function evaluateNullability()
    {
        return ($this->internals[$this->S]->nullable === true);
    }

    // S is the first
    public function firstSet()
    {
        return array($this->internals[$this->S]);
    }
}
