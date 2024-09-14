<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Throwable;

/**
 * This interface should be implemented by all exceptions in the Repository
 * namespace.
 */
interface RepositoryException extends Throwable
{
}
