<?php

namespace ferno\loco;

/**
 * Tiny subclass is ironically much more useful than GreedyMultiParser
 */
class GreedyStarParser extends GreedyMultiParser
{
    public function __construct($internal, $callback = null)
    {
        $this->string = "new " . get_class() . "(" . $internal . ")";
        parent::__construct($internal, 0, null, $callback);
    }
}