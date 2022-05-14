<?php
// Each Mult consists of a multiplicand (a Charclass or a Pattern) and a Multiplier
class Mult
{
    public $multiplicand;
    public $multiplier;

    public function __construct($multiplicand, $multiplier)
    {
        if (!is_a($multiplicand, "Charclass") && !is_a($multiplicand, "Pattern")) {
            throw new Exception("Not a Charclass or Pattern: ".var_export($multiplicand, true));
        }
        if (!is_a($multiplier, "Multiplier")) {
            throw new Exception("Not a Multiplier: ".var_export($multiplier, true));
        }
        $this->multiplicand = $multiplicand;
        $this->multiplier = $multiplier;
    }

    public function __toString()
    {
        if (is_a($this->multiplicand, "Pattern")) {
            return "(".$this->multiplicand.")".$this->multiplier;
        }
        return $this->multiplicand.$this->multiplier;
    }
}
