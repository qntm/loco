<?php

namespace ferno\loco;

use RuntimeException;

/**
 * This occurs at Grammar instantiation time, e.g. left-recursion, null-stars,
 * miscellaneous housekeeping errors
 */
class GrammarException extends RuntimeException {
    const _CLASS = __CLASS__;
}
