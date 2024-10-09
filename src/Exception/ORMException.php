<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\ORMException as BaseORMException;

/**
 * Should become an interface in 3.0
 *
 * @phpstan-ignore class.extendsDeprecatedClass
 */
class ORMException extends BaseORMException
{
}
