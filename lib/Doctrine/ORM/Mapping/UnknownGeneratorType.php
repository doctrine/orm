<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class UnknownGeneratorType extends \Exception implements ORMException
{
    public static function create(string $generatorType) : self
    {
        return new self('Unknown generator type: ' . $generatorType);
    }
}
