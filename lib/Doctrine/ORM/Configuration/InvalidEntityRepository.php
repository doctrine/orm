<?php

declare(strict_types=1);

namespace Doctrine\ORM\Configuration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\ConfigurationException;

final class InvalidEntityRepository extends \Exception implements ConfigurationException
{
    public static function fromClassName(string $className) : self
    {
        return new self(
            "Invalid repository class '" . $className . "'. It must be a " . ObjectRepository::class . '.'
        );
    }
}
