<?php


namespace ferno\loco\grammar\regex;

use Exception;

// A Charclass is a set of characters, possibly negated.
class Charclass
{
    public $chars = array();
    public $negateMe = false;

    function __construct($chars, $negateMe = false)
    {
        if (!is_string($chars)) {
            throw new Exception("Not a string: " . var_export($chars, true));
        }
        if (!is_bool($negateMe)) {
            throw new Exception("Not a boolean: " . var_export($negateMe, true));
        }
        for ($i = 0; $i < strlen($chars); $i++) {
            $char = $chars[$i];
            if (!in_array($char, $this->chars)) {
                $this->chars[] = $char;
            }
        }
        $this->negateMe = $negateMe;
    }

    // This is all a bit naive but it gives you the general picture
    public function __toString()
    {
        if (count($this->chars) === 0) {
            if ($this->negateMe) {
                return ".";
            }
            throw new Exception("What");
        }

        if (count($this->chars) === 1 && $this->negateMe === false) {
            return $this->chars[0];
        }

        if ($this->negateMe) {
            return "[^" . implode("", $this->chars) . "]";
        }

        return "[" . implode("", $this->chars) . "]";
    }
}