<?php


namespace ferno\loco\grammar\regex;


// A Multiplier consists of a non-negative integer lower bound and a non-negative
// integer upper bound greater than or equal to the lower bound.
// The upper bound can also be null (infinity)
use Exception;

class Multiplier
{
    public $lower;
    public $upper;

    public function __construct($lower, $upper)
    {
        if (!is_int($lower)) {
            throw new Exception("Not an integer: " . var_export($lower, true));
        }
        if (!is_int($upper) && $upper !== null) {
            throw new Exception("Not an integer or null: " . var_export($upper, true));
        }
        if ($upper !== null && !($lower <= $upper)) {
            throw new Exception("Upper: " . var_export($upper, true) . " is less than lower: " . var_export($lower, true));
        }
        $this->lower = $lower;
        $this->upper = $upper;
    }

    public function __toString()
    {
        if ($this->lower == 1 && $this->upper == 1) {
            return "";
        }
        if ($this->lower == 0 && $this->upper == 1) {
            return "?";
        }
        if ($this->lower == 0 && $this->upper === null) {
            return "*";
        }
        if ($this->lower == 1 && $this->upper === null) {
            return "+";
        }
        if ($this->upper === null) {
            return "{" . $this->lower . ",}";
        }
        if ($this->lower == $this->upper) {
            return "{" . $this->lower . "}";
        }
        return "{" . $this->lower . "," . $this->upper . "}";
    }
}