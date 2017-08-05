<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Exception thrown when an ORM query unexpectedly returns more than one result.
 *
 * @author robo
 * @since 2.0
 */
class NonUniqueResultException extends UnexpectedResultException
{
}
