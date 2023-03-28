<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;
use BackedEnum;

use function in_array;
use function property_exists;

/** @template-implements ArrayAccess<string, mixed> */
final class FieldMapping implements ArrayAccess
{
    use ArrayAccessImplementation;

    /** @var int|null The database length of the column. Optional. Default value taken from the type. */
    public int|null $length = null;
    /**
     * @var bool|null Marks the field as the primary key of the entity. Multiple
     * fields of an entity can have the id attribute, forming a composite key.
     */
    public bool|null $id                 = null;
    public bool|null $nullable           = null;
    public bool|null $notInsertable      = null;
    public bool|null $notUpdatable       = null;
    public string|null $columnDefinition = null;
    /** ClassMetadata::GENERATED_* */
    public int|null $generated = null;
    /** @var class-string<BackedEnum>|null */
    public string|null $enumType = null;
    /**
     * @var int|null The precision of a decimal column.
     * Only valid if the column type is decimal
     */
    public int|null $precision = null;
    /**
     * @var int|null The scale of a decimal column.
     * Only valid if the column type is decimal
     */
    public int|null $scale = null;
    /** @var bool|null Whether a unique constraint should be generated for the column. */
    public bool|null $unique = null;
    /**
     * @var class-string|null This is set when the field is inherited by this
     * class from another (inheritance) parent <em>entity</em> class. The value
     * is the FQCN of the topmost entity class that contains mapping information
     * for this field. (If there are transient classes in the class hierarchy,
     * these are ignored, so the class property may in fact come from a class
     * further up in the PHP class hierarchy.)
     * Fields initially declared in mapped superclasses are
     * <em>not</em> considered 'inherited' in the nearest entity subclasses.
     */
    public string|null $inherited = null;

    public string|null $originalClass = null;
    public string|null $originalField = null;
    public bool|null $quoted          = null;
    /**
     * @var class-string|null This is set when the field does not appear for
     * the first time in this class, but is originally declared in another
     * parent <em>entity or mapped superclass</em>. The value is the FQCN of
     * the topmost non-transient class that contains mapping information for
     * this field.
     */
    public string|null $declared      = null;
    public string|null $declaredField = null;
    public array|null $options        = null;
    public bool|null $version         = null;
    public string|int|null $default   = null;

    /**
     * @param string $type       The type name of the mapped field. Can be one of
     *                           Doctrine's mapping types or a custom mapping type.
     * @param string $fieldName  The name of the field in the Entity.
     * @param string $columnName The column name. Optional. Defaults to the field name.
     */
    public function __construct(
        public string $type,
        public string $fieldName,
        public string $columnName,
    ) {
    }

    /** @param array{type: string, fieldName: string, columnName: string} $mappingArray */
    public static function fromMappingArray(array $mappingArray): self
    {
        $mapping = new self(
            $mappingArray['type'],
            $mappingArray['fieldName'],
            $mappingArray['columnName'],
        );
        foreach ($mappingArray as $key => $value) {
            if (in_array($key, ['type', 'fieldName', 'columnName'])) {
                continue;
            }

            if (property_exists($mapping, $key)) {
                $mapping->$key = $value;
            }
        }

        return $mapping;
    }
}
