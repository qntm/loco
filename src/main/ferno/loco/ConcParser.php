<?php

namespace ferno\loco;

/**
 * Match several things in a row. Callback should accept one argument
 * for each parser listed.
 */
class ConcParser extends MonoParser
{
    public function __construct($internals, $callback = null)
    {
        $this->string = "new " . get_class() . "(" . $this->serializeArray($internals) . ")";
        parent::__construct($internals, $callback);
    }

    /**
     * Default callback (this should be used rarely) returns all arguments as
     * an array. In the majority of cases the user should specify a callback.
     */
    public function defaultCallback()
    {
        return func_get_args();
    }

    public function getResult($string, $i = 0)
    {
        $j = $i;
        $args = array();
        foreach ($this->internals as $parser) {
            $match = $parser->match($string, $j);
            $j = $match["j"];
            $args[] = $match["value"];
        }
        return array("j" => $j, "args" => $args);
    }

    /**
     * First-set is built up as follows...
     */
    public function firstSet()
    {
        $firstSet = array();
        foreach ($this->internals as $internal) {
            # The first $internal is always in the first-set
            $firstSet[] = $internal;

            # If $internal was nullable, then the next internal in the
            # list is also in the first-set, so continue the loop.
            # Otherwise we are done.
            if (! $internal->nullable) {
                break;
            }
        }
        return $firstSet;
    }

    /**
     * only nullable if everything in the list is nullable
     */
    public function evaluateNullability()
    {
        foreach ($this->internals as $internal) {
            if (! $internal->nullable) {
                return false;
            }
        }
        return true;
    }
}
