<?php

namespace ferno\loco;

/**
 * LookaheadParser matches everything up until any one of the provided
 * $lookaheadStrings is encountered. So this works like a negative
 * lookahead regular expression, but is less flexible.
 */
class LookaheadParser extends StaticParser
{
    private $lookaheadStrings;

    public function __construct($lookaheadStrings, $callback = null)
    {
        if (! is_array($lookaheadStrings)) {
            throw new GrammarException("\$lookaheadStrings must be an array");
        } else if (count($lookaheadStrings) == 0) {
            throw new GrammarException("\$lookaheadStrings must not be empty");
        }
        $this->lookaheadStrings = $lookaheadStrings;

        $this->string = "new ".get_class()."(".$this->serializeArray($lookaheadStrings).")";

        parent::__construct($callback);
    }

    /**
     * default callback: return the string that was matched
     */
    public function defaultCallback()
    {
        return func_get_arg(0);
    }

    public function getResult($string, $i = 0)
    {
        $lookaheadFirstStringPos = strlen($string);
        foreach ($this->lookaheadStrings as $lookahead) {
            $pos = strpos($string, $lookahead, $i);
            if ($pos !== FALSE AND $pos < $lookaheadFirstStringPos) {
                $lookaheadFirstStringPos = $pos;
            }
        }

        if ($lookaheadFirstStringPos == $i) {
            throw new ParseFailureException($this." did not match anything ", $i, $string);
        } else {
            return array(
                "j" => $lookaheadFirstStringPos,
                "args" => array(substr($string, $i, $lookaheadFirstStringPos - $i)),
            );
        }
    }

    public function evaluateNullability()
    {
        return FALSE;
    }
}
