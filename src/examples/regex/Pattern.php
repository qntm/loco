<?php
// Each Pattern is an alternation between several "Concs"
// This is the top-level Pattern object returned by the lexer.

namespace Ferno\Loco\examples\regex;

class Pattern
{
    public $concs;

    public function __construct($concs)
    {
        foreach ($concs as $conc) {
            if (!($conc instanceof Conc)) {
                throw new ParseFailureException("Not a Conc: ".var_export($conc, true));
            }
        }
        $this->concs = $concs;
    }

    public function __toString()
    {
        return implode("|", $this->concs);
    }
}
