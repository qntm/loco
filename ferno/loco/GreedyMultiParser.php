<?php

namespace ferno\loco;

/**
 * Callback accepts a single argument containing all submatches, however many
 */
class GreedyMultiParser extends MonoParser
{
    private $lower;
    public $optional;

    public function __construct($internal, $lower, $upper, $callback = null)
    {
        $this->lower = $lower;
        if (is_null($upper)) {
            $this->optional = null;
        } else {
            if ($upper < $lower) {
                throw new GrammarException("Can't create a " . get_class(
                ) . " with lower limit " . var_export(
                    $lower,
                    true
                ) . " and upper limit " . var_export($upper, true));
            }
            $this->optional = $upper - $lower;
        }
        $this->string = "new " . get_class() . "(" . $internal . ", " . var_export($lower, true) . ", " . var_export(
                $upper,
                true
            ) . ")";
        parent::__construct(array($internal), $callback);
    }

    /**
     * default callback: just return the list
     */
    public function defaultCallback()
    {
        return func_get_args();
    }

    public function getResult($string, $i = 0)
    {

        $result = array("j" => $i, "args" => array());

        # First do the non-optional segment
        # Any parse failures here are terminal
        for ($k = 0; $k < $this->lower; $k ++) {
            $match = $this->internals[0]->match($string, $result["j"]);
            $result["j"] = $match["j"];
            $result["args"][] = $match["value"];
        }

        # next, the optional segment
        # null => no upper limit
        for ($k = 0; $this->optional === null || $k < $this->optional; $k ++) {
            try {
                $match = $this->internals[0]->match($string, $result["j"]);
                $result["j"] = $match["j"];
                $result["args"][] = $match["value"];
            } catch (ParseFailureException $e) {
                break;
            }
        }
        return $result;
    }

    /**
     * nullable if lower limit is zero OR internal is nullable.
     */
    public function evaluateNullability()
    {
        return ($this->lower == 0 || $this->internals[0]->nullable === true);
    }

    /**
     * This parser contains only one internal
     */
    public function firstSet()
    {
        return array($this->internals[0]);
    }
}