<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;

/**
 * {@inheritDoc}
 *
 * @todo remove or rename ClassMetadataInfo to ClassMetadata
 * @template-covariant T of object
 * @template-extends ClassMetadataInfo<T>
 * @psalm-type FieldMapping = array{
 *      type: string,
 *      fieldName: string,
 *      columnName: string,
 *      length?: int,
 *      id?: bool,
 *      nullable?: bool,
 *      notInsertable?: bool,
 *      notUpdatable?: bool,
 *      generated?: int,
 *      enumType?: class-string<BackedEnum>,
 *      columnDefinition?: string,
 *      precision?: int,
 *      scale?: int,
 *      unique?: bool,
 *      inherited?: class-string,
 *      originalClass?: class-string,
 *      originalField?: string,
 *      quoted?: bool,
 *      requireSQLConversion?: bool,
 *      declared?: class-string,
 *      declaredField?: string,
 *      options?: array<string, mixed>,
 *      version?: string,
 *      default?: string|int,
 * }
 * @psalm-type JoinColumnData = array{
 *     name: string,
 *     referencedColumnName: string,
 *     unique?: bool,
 *     quoted?: bool,
 *     fieldName?: string,
 *     onDelete?: string,
 *     columnDefinition?: string,
 *     nullable?: bool,
 * }
 * @psalm-type AssociationMapping = array{
 *     cache?: array,
 *     cascade: array<string>,
 *     declared?: class-string,
 *     fetch: mixed,
 *     fieldName: string,
 *     id?: bool,
 *     inherited?: class-string,
 *     indexBy?: string,
 *     inversedBy: string|null,
 *     isCascadeRemove: bool,
 *     isCascadePersist: bool,
 *     isCascadeRefresh: bool,
 *     isCascadeMerge: bool,
 *     isCascadeDetach: bool,
 *     isOnDeleteCascade?: bool,
 *     isOwningSide: bool,
 *     joinColumns?: array<JoinColumnData>,
 *     joinColumnFieldNames?: array<string, string>,
 *     joinTable?: array,
 *     joinTableColumns?: list<mixed>,
 *     mappedBy: string|null,
 *     orderBy?: array,
 *     originalClass?: class-string,
 *     originalField?: string,
 *     orphanRemoval?: bool,
 *     relationToSourceKeyColumns?: array,
 *     relationToTargetKeyColumns?: array,
 *     sourceEntity: class-string,
 *     sourceToTargetKeyColumns?: array<string, string>,
 *     targetEntity: class-string,
 *     targetToSourceKeyColumns?: array<string, string>,
 *     type: int,
 *     unique?: bool,
 * }
 * @psalm-type DiscriminatorColumnMapping = array{
 *     name: string,
 *     fieldName: string,
 *     type: string,
 *     length?: int,
 *     columnDefinition?: string|null,
 *     enumType?: class-string<BackedEnum>|null,
 *     options?: array<string, mixed>,
 * }
 * @psalm-type EmbeddedClassMapping = array{
 *    class: class-string,
 *    columnPrefix: string|null,
 *    declaredField: string|null,
 *    originalField: string|null,
 *    inherited?: class-string,
 *    declared?: class-string,
 * }
 */
class ClassMetadata extends ClassMetadataInfo
{
    /**
     * Repeating the ClassMetadataInfo constructor to infer correctly the template with PHPStan
     *
     * @see https://github.com/doctrine/orm/issues/8709
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     * @psalm-param class-string<T> $entityName
     */
    public function __construct($entityName, ?NamingStrategy $namingStrategy = null, ?TypedFieldMapper $typedFieldMapper = null)
    {
        parent::__construct($entityName, $namingStrategy, $typedFieldMapper);
    }
}
