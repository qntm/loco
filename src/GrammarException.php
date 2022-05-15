<?php declare(strict_types=1);
namespace Ferno\Loco;

use Exception;

// This occurs at Grammar instantiation time, e.g. left-recursion, null-stars,
// miscellaneous housekeeping errors
class GrammarException extends Exception
{
}
