<?php

namespace Darya\Service\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when a service could not be resolved from a container.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
