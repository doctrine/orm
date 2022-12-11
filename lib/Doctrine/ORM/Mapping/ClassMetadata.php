<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Types\Type;

/**
 * {@inheritDoc}
 *
 * @psalm-import-type ScalarName from ClassMetadataInfo
 * @todo remove or rename ClassMetadataInfo to ClassMetadata
 * @template-covariant T of object
 * @template-extends ClassMetadataInfo<T>
 */
class ClassMetadata extends ClassMetadataInfo
{
    /**
     * Repeating the ClassMetadataInfo constructor to infer correctly the template with PHPStan
     *
     * @see https://github.com/doctrine/orm/issues/8709
     *
     * @param string                                                    $entityName         The name of the entity class the new instance is used for.
     * @param array<class-string|ScalarName, class-string<Type>|string> $typedFieldMappings
     * @psalm-param class-string<T> $entityName
     */
    public function __construct($entityName, ?NamingStrategy $namingStrategy = null, array $typedFieldMappings = [])
    {
        parent::__construct($entityName, $namingStrategy, $typedFieldMappings);
    }
}
