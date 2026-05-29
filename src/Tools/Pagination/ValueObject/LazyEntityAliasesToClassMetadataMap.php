<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination\ValueObject;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use RuntimeException;

use function is_string;
use function sprintf;

/** @internal */
final class LazyEntityAliasesToClassMetadataMap
{
    /** @var array<string, string|ClassMetadata<object>> */
    private array $map = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function hasEntityAlias(string $entityAlias): bool
    {
        return isset($this->map[$entityAlias]);
    }

    /** @param string|ClassMetadata<object> $classNameOrClassMetadata */
    public function addEntityAlias(string $entityAlias, string|ClassMetadata $classNameOrClassMetadata): void
    {
        $this->map[$entityAlias] = $classNameOrClassMetadata;
    }

    /** @return ClassMetadata<object> */
    public function getClassMetadata(string $entityAlias): ClassMetadata
    {
        $classNameOrClassMetadata = $this->map[$entityAlias] ?? null;

        if ($classNameOrClassMetadata === null) {
            throw new RuntimeException(sprintf('Unknown entity alias: "%s".', $entityAlias));
        }

        // Load entity class metadata.
        if (is_string($classNameOrClassMetadata)) {
            $classMetadata = $this->entityManager->getClassMetadata($classNameOrClassMetadata);
            $this->addEntityAlias($entityAlias, $classMetadata);

            return $classMetadata;
        }

        return $classNameOrClassMetadata;
    }
}
