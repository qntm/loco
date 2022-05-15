<?php
// Each Conc is a concatenation of several "Mults"

namespace Ferno\Loco;

class Conc
{
    public $mults;

    public function __construct($mults)
    {
        foreach ($mults as $mult) {
            if (!($mult instanceof Mult)) {
                throw new ParseFailureException("Not a Mult: ".var_export($mult, true));
            }
        }
        $this->mults = $mults;
    }

    public function __toString()
    {
        return implode("", $this->mults);
    }
}
