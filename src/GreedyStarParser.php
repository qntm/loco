<?php declare(strict_types=1);
namespace Ferno\Loco;

// Tiny subclass is ironically much more useful than GreedyMultiParser
class GreedyStarParser extends GreedyMultiParser
{
    public function __construct($internal, $callback = null)
    {
        parent::__construct($internal, 0, null, $callback);
    }
}
