<?php
// Each Conc is a concatenation of several "Mults"
class Conc
{
    public $mults;

    public function __construct($mults)
    {
        foreach ($mults as $mult) {
            if (!is_a($mult, "Mult")) {
                throw new Exception("Not a Mult: ".var_export($mult, true));
            }
        }
        $this->mults = $mults;
    }

    public function __toString()
    {
        return implode("", $this->mults);
    }
}
