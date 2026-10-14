<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * HelloAsso did not answer, or refused the request.
 */
class HelloAssoUnavailable extends RuntimeException {}
