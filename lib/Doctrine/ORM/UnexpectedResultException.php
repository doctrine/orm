<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\ORM\Exception\ORMException;

/**
 * Exception for a unexpected query result.
 *
 * @final
 */
class UnexpectedResultException extends \RuntimeException implements ORMException
{
}
