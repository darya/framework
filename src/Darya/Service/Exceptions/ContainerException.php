<?php

namespace Darya\Service\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * A service container exception.
 *
 * Represents a general error during service container operation.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
