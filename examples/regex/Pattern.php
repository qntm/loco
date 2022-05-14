<?php
namespace Ferno\Loco\Examples\RegEx;

// Each Pattern is an alternation between several "Concs"
// This is the top-level Pattern object returned by the lexer.
class Pattern
{
    public $concs;

    public function __construct($concs)
    {
        foreach ($concs as $conc) {
            if (!is_a($conc, "Conc")) {
                throw new Exception("Not a Conc: ".var_export($conc, true));
            }
        }
        $this->concs = $concs;
    }

    public function __toString()
    {
        return implode("|", $this->concs);
    }
}
