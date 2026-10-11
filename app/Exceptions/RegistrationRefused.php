<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A registration on a slot is not allowed; $reason is a key of lang/fr/planning.php "refusals".
 */
class RegistrationRefused extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct(__('planning.refusals.'.$reason));
    }
}
