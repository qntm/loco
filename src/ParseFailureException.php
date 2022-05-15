<?php
namespace Ferno\Loco;

use Exception;

// Occurs when any parser fails to parse what it's supposed to
// parse. Usually non-fatal and almost always caught
class ParseFailureException extends Exception
{
    public function __construct($message, $i, $string, $code = 0, Exception $previous = null)
    {
        $message .= " at position " . var_export($i, true) . " in string " . var_export($string, true);
        parent::__construct($message, $code);
    }
}
