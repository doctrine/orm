<?php

declare(strict_types=1);

namespace Doctrine\StaticAnalysis;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * EntityManagerInterface::getClassMetadata() is templated only for Psalm,
 * because of limitations in PHPStan.
 *
 * @see https://github.com/phpstan/phpstan/issues/5175#issuecomment-861437050
 */
abstract class GetMetadata
{
    /**
     * @param string|object $class
     * @psalm-param class-string|object $class
     */
    abstract public function getEntityManager($class): EntityManagerInterface;

    /**
     * @psalm-param class-string<TObject> $class
     *
     * @psalm-return ClassMetadata<TObject>
     *
     * @psalm-template TObject of object
     */
    public function __invoke(string $class): ClassMetadata
    {
        return $this->getEntityManager($class)->getClassMetadata($class);
    }
}
