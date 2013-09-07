<?php


namespace ferno\loco\grammar\regex;

// Each Pattern is an alternation between several "Concs"
// This is the top-level Pattern object returned by the lexer.
use Exception;

class Pattern
{
    public $concs;

    public function __construct($concs)
    {
        foreach ($concs as $conc) {
            if (!($conc instanceof Conc)) {
                throw new Exception("Not a Conc: " . var_export($conc, true));
            }
        }
        $this->concs = $concs;
    }

    public function __toString()
    {
        return implode("|", $this->concs);
    }
}