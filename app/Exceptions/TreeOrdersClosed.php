<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Families can no longer order, pay online or cancel after the deadline.
 */
class TreeOrdersClosed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('trees.errors.closed'));
    }
}
