<?php


namespace ferno\loco\grammar\regex;

// Each Conc is a concatenation of several "Mults"
use Exception;

class Conc
{
    public $mults;

    public function __construct($mults)
    {
        foreach ($mults as $mult) {
            if (!($mult instanceof Mult)) {
                throw new Exception("Not a Mult: " . var_export($mult, true));
            }
        }
        $this->mults = $mults;
    }

    public function __toString()
    {
        return implode("", $this->mults);
    }
} 