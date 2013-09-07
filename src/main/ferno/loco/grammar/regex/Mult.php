<?php


namespace ferno\loco\grammar\regex;

// Each Mult consists of a multiplicand (a Charclass or a Pattern) and a Multiplier
use Exception;

class Mult
{
    public $multiplicand;
    public $multiplier;

    public function __construct($multiplicand, $multiplier)
    {
        if (!($multiplicand instanceof Charclass) && !($multiplicand instanceof Pattern)) {
            throw new Exception("Not a Charclass or Pattern: " . var_export($multiplicand, true));
        }
        if (!($multiplier instanceof Multiplier)) {
            throw new Exception("Not a Multiplier: " . var_export($multiplier, true));
        }
        $this->multiplicand = $multiplicand;
        $this->multiplier = $multiplier;
    }

    public function __toString()
    {
        if ($this->multiplicand instanceof Pattern) {
            return "(" . $this->multiplicand . ")" . $this->multiplier;
        }
        return $this->multiplicand . $this->multiplier;
    }
}