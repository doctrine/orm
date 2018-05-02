<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

/**
 * Exception for a unexpected query result.
 */
abstract class UnexpectedResult extends \RuntimeException implements ORMException
{
}
