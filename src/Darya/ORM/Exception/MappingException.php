<?php

namespace Darya\ORM\Exception;

use RuntimeException;
use Throwable;

/**
 * Darya's mapping exception.
 *
 * Thrown for entity mapping related errors.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class MappingException extends RuntimeException
{
	public static function unknownStorage(string $storage, Throwable $previous = null): self {
		return new static("Unknown storage '$storage'", null, $previous);
	}
}