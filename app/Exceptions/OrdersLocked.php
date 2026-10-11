<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Ingredient orders can no longer change after the deadline.
 */
class OrdersLocked extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('cookies.orders.locked'));
    }
}
