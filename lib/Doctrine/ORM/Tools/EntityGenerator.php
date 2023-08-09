<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_sum;
use function array_unique;
use function array_values;
use function basename;
use function chmod;
use function class_exists;
use function copy;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function ltrim;
use function max;
use function mkdir;
use function sprintf;
use function str_contains;
use function str_repeat;
use function str_replace;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function token_get_all;
use function ucfirst;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use const PHP_VERSION_ID;
use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_DOUBLE_COLON;
use const T_FUNCTION;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_NS_SEPARATOR;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_STRING;
use const T_VAR;
use const T_WHITESPACE;

/**
 * Generic class used to generate PHP5 entity classes from ClassMetadataInfo instances.
 *
 *     [php]
 *     $classes = $em->getClassMetadataFactory()->getAllMetadata();
 *
 *     $generator = new \Doctrine\ORM\Tools\EntityGenerator();
 *     $generator->setGenerateAnnotations(true);
 *     $generator->setGenerateStubMethods(true);
 *     $generator->setRegenerateEntityIfExists(false);
 *     $generator->setUpdateEntityIfExists(true);
 *     $generator->generate($classes, '/path/to/generate/entities');
 *
 * @deprecated 2.7 This class is being removed from the ORM and won't have any replacement
 *
 * @link    www.doctrine-project.org
 */
class EntityGenerator
{
    /**
     * Specifies class fields should be protected.
     */
    public const FIELD_VISIBLE_PROTECTED = 'protected';

    /**
     * Specifies class fields should be private.
     */
    public const FIELD_VISIBLE_PRIVATE = 'private';

    /** @var bool */
    protected $backupExisting = true;

    /**
     * The extension to use for written php files.
     *
     * @var string
     */
    protected $extension = '.php';

    /**
     * Whether or not the current ClassMetadataInfo instance is new or old.
     *
     * @var bool
     */
    protected $isNew = true;

    /** @var mixed[] */
    protected $staticReflection = [];

    /**
     * Number of spaces to use for indention in generated code.
     *
     * @var int
     */
    protected $numSpaces = 4;

    /**
     * The actual spaces to use for indention.
     *
     * @var string
     */
    protected $spaces = '    ';

    /**
     * The class all generated entities should extend.
     *
     * @var string
     */
    protected $classToExtend;

    /**
     * Whether or not to generation annotations.
     *
     * @var bool
     */
    protected $generateAnnotations = false;

    /** @var string */
    protected $annotationsPrefix = '';

    /**
     * Whether or not to generate sub methods.
     *
     * @var bool
     */
    protected $generateEntityStubMethods = false;

    /**
     * Whether or not to update the entity class if it exists already.
     *
     * @var bool
     */
    protected $updateEntityIfExists = false;

    /**
     * Whether or not to re-generate entity class if it exists already.
     *
     * @var bool
     */
    protected $regenerateEntityIfExists = false;

    /**
     * Visibility of the field
     *
     * @var string
     */
    protected $fieldVisibility = 'private';

    /**
     * Whether or not to make generated embeddables immutable.
     *
     * @var bool
     */
    protected $embeddablesImmutable = false;

    /**
     * Hash-map for handle types.
     *
     * @psalm-var array<Types::*|'json_array', string>
     */
    protected $typeAlias = [
        Types::DATETIMETZ_MUTABLE => '\DateTime',
        Types::DATETIME_MUTABLE   => '\DateTime',
        Types::DATE_MUTABLE       => '\DateTime',
        Types::TIME_MUTABLE       => '\DateTime',
        Types::OBJECT             => '\stdClass',
        Types::INTEGER            => 'int',
        Types::BIGINT             => 'int',
        Types::SMALLINT           => 'int',
        Types::TEXT               => 'string',
        Types::BLOB               => 'string',
        Types::DECIMAL            => 'string',
        Types::GUID               => 'string',
        'json_array'              => 'array',
        Types::JSON               => 'array',
        Types::SIMPLE_ARRAY       => 'array',
        Types::BOOLEAN            => 'bool',
    ];

    /**
     * Hash-map to handle generator types string.
     *
     * @psalm-var array<ClassMetadataInfo::GENERATOR_TYPE_*, string>
     */
    protected static $generatorStrategyMap = [
        ClassMetadataInfo::GENERATOR_TYPE_AUTO      => 'AUTO',
        ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE  => 'SEQUENCE',
        ClassMetadataInfo::GENERATOR_TYPE_IDENTITY  => 'IDENTITY',
        ClassMetadataInfo::GENERATOR_TYPE_NONE      => 'NONE',
        ClassMetadataInfo::GENERATOR_TYPE_UUID      => 'UUID',
        ClassMetadataInfo::GENERATOR_TYPE_CUSTOM    => 'CUSTOM',
    ];

    /**
     * Hash-map to handle the change tracking policy string.
     *
     * @psalm-var array<ClassMetadataInfo::CHANGETRACKING_*, string>
     */
    protected static $changeTrackingPolicyMap = [
        ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT  => 'DEFERRED_IMPLICIT',
        ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT  => 'DEFERRED_EXPLICIT',
        ClassMetadataInfo::CHANGETRACKING_NOTIFY             => 'NOTIFY',
    ];

    /**
     * Hash-map to handle the inheritance type string.
     *
     * @psalm-var array<ClassMetadataInfo::INHERITANCE_TYPE_*, string>
     */
    protected static $inheritanceTypeMap = [
        ClassMetadataInfo::INHERITANCE_TYPE_NONE            => 'NONE',
        ClassMetadataInfo::INHERITANCE_TYPE_JOINED          => 'JOINED',
        ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE    => 'SINGLE_TABLE',
        ClassMetadataInfo::INHERITANCE_TYPE_TABLE_PER_CLASS => 'TABLE_PER_CLASS',
    ];

    /** @var string */
    protected static $classTemplate =
    '<?php

<namespace>
<useStatement>
<entityAnnotation>
<entityClassName>
{
<entityBody>
}
';

    /** @var string */
    protected static $getMethodTemplate =
    '/**
 * <description>
 *
 * @return <variableType>
 */
public function <methodName>()
{
<spaces>return $this-><fieldName>;
}';

    /** @var string */
    protected static $setMethodTemplate =
    '/**
 * <description>
 *
 * @param <variableType> $<variableName>
 *
 * @return <entity>
 */
public function <methodName>(<methodTypeHint>$<variableName><variableDefault>)
{
<spaces>$this-><fieldName> = $<variableName>;

<spaces>return $this;
}';

    /** @var string */
    protected static $addMethodTemplate =
    '/**
 * <description>
 *
 * @param <variableType> $<variableName>
 *
 * @return <entity>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName>[] = $<variableName>;

<spaces>return $this;
}';

    /** @var string */
    protected static $removeMethodTemplate =
    '/**
 * <description>
 *
 * @param <variableType> $<variableName>
 *
 * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>return $this-><fieldName>->removeElement($<variableName>);
}';

    /** @var string */
    protected static $lifecycleCallbackMethodTemplate =
    '/**
 * @<name>
 */
public function <methodName>()
{
<spaces>// Add your code here
}';

    /** @var string */
    protected static $constructorMethodTemplate =
    '/**
 * Constructor
 */
public function __construct()
{
<spaces><collections>
}
';

    /** @var string */
    protected static $embeddableConstructorMethodTemplate =
    '/**
 * Constructor
 *
 * <paramTags>
 */
public function __construct(<params>)
{
<spaces><fields>
}
';

    /** @var Inflector */
    protected $inflector;

    public function __construct()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/8458',
            '%s is deprecated with no replacement',
            self::class
        );

        $this->annotationsPrefix = 'ORM\\';
        $this->inflector         = InflectorFactory::create()->build();
    }

    /**
     * Generates and writes entity classes for the given array of ClassMetadataInfo instances.
     *
     * @param string $outputDirectory
     * @psalm-param list<ClassMetadataInfo> $metadatas
     *
     * @return void
     */
    public function generate(array $metadatas, $outputDirectory)
    {
        foreach ($metadatas as $metadata) {
            $this->writeEntityClass($metadata, $outputDirectory);
        }
    }

    /**
     * Generates and writes entity class to disk for the given ClassMetadataInfo instance.
     *
     * @param string $outputDirectory
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function writeEntityClass(ClassMetadataInfo $metadata, $outputDirectory)
    {
        $path = $outputDirectory . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->extension;
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->isNew = ! file_exists($path) || $this->regenerateEntityIfExists;

        if (! $this->isNew) {
            $this->parseTokensInEntityFile(file_get_contents($path));
        } else {
            $this->staticReflection[$metadata->name] = ['properties' => [], 'methods' => []];
        }

        if ($this->backupExisting && file_exists($path)) {
            $backupPath = dirname($path) . DIRECTORY_SEPARATOR . basename($path) . '~';
            if (! copy($path, $backupPath)) {
                throw new RuntimeException('Attempt to backup overwritten entity file but copy operation failed.');
            }
        }

        // If entity doesn't exist or we're re-generating the entities entirely
        if ($this->isNew) {
            file_put_contents($path, $this->generateEntityClass($metadata));
        // If entity exists and we're allowed to update the entity class
        } elseif ($this->updateEntityIfExists) {
            file_put_contents($path, $this->generateUpdatedEntityClass($metadata, $path));
        }

        chmod($path, 0664);
    }

    /**
     * Generates a PHP5 Doctrine 2 entity class from the given ClassMetadataInfo instance.
     *
     * @return string
     */
    public function generateEntityClass(ClassMetadataInfo $metadata)
    {
        $placeHolders = [
            '<namespace>',
            '<useStatement>',
            '<entityAnnotation>',
            '<entityClassName>',
            '<entityBody>',
        ];

        $replacements = [
            $this->generateEntityNamespace($metadata),
            $this->generateEntityUse(),
            $this->generateEntityDocBlock($metadata),
            $this->generateEntityClassName($metadata),
            $this->generateEntityBody($metadata),
        ];

        $code = str_replace($placeHolders, $replacements, static::$classTemplate);

        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generates the updated code for the given ClassMetadataInfo and entity at path.
     *
     * @param string $path
     *
     * @return string
     */
    public function generateUpdatedEntityClass(ClassMetadataInfo $metadata, $path)
    {
        $currentCode = file_get_contents($path);

        $body = $this->generateEntityBody($metadata);
        $body = str_replace('<spaces>', $this->spaces, $body);
        $last = strrpos($currentCode, '}');

        return substr($currentCode, 0, $last) . $body . ($body ? "\n" : '') . "}\n";
    }

    /**
     * Sets the number of spaces the exported class should have.
     *
     * @param int $numSpaces
     *
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces    = str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;
    }

    /**
     * Sets the extension to use when writing php files to disk.
     *
     * @param string $extension
     *
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Sets the name of the class the generated classes should extend from.
     *
     * @param string $classToExtend
     *
     * @return void
     */
    public function setClassToExtend($classToExtend)
    {
        $this->classToExtend = $classToExtend;
    }

    /**
     * Sets whether or not to generate annotations for the entity.
     *
     * @param bool $bool
     *
     * @return void
     */
    public function setGenerateAnnotations($bool)
    {
        $this->generateAnnotations = $bool;
    }

    /**
     * Sets the class fields visibility for the entity (can either be private or protected).
     *
     * @param string $visibility
     *
     * @return void
     *
     * @throws InvalidArgumentException
     *
     * @psalm-assert self::FIELD_VISIBLE_* $visibility
     */
    public function setFieldVisibility($visibility)
    {
        if ($visibility !== self::FIELD_VISIBLE_PRIVATE && $visibility !== self::FIELD_VISIBLE_PROTECTED) {
            throw new InvalidArgumentException('Invalid provided visibility (only private and protected are allowed): ' . $visibility);
        }

        $this->fieldVisibility = $visibility;
    }

    /**
     * Sets whether or not to generate immutable embeddables.
     *
     * @param bool $embeddablesImmutable
     *
     * @return void
     */
    public function setEmbeddablesImmutable($embeddablesImmutable)
    {
        $this->embeddablesImmutable = (bool) $embeddablesImmutable;
    }

    /**
     * Sets an annotation prefix.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setAnnotationPrefix($prefix)
    {
        $this->annotationsPrefix = $prefix;
    }

    /**
     * Sets whether or not to try and update the entity if it already exists.
     *
     * @param bool $bool
     *
     * @return void
     */
    public function setUpdateEntityIfExists($bool)
    {
        $this->updateEntityIfExists = $bool;
    }

    /**
     * Sets whether or not to regenerate the entity if it exists.
     *
     * @param bool $bool
     *
     * @return void
     */
    public function setRegenerateEntityIfExists($bool)
    {
        $this->regenerateEntityIfExists = $bool;
    }

    /**
     * Sets whether or not to generate stub methods for the entity.
     *
     * @param bool $bool
     *
     * @return void
     */
    public function setGenerateStubMethods($bool)
    {
        $this->generateEntityStubMethods = $bool;
    }

    /**
     * Should an existing entity be backed up if it already exists?
     *
     * @param bool $bool
     *
     * @return void
     */
    public function setBackupExisting($bool)
    {
        $this->backupExisting = $bool;
    }

    public function setInflector(Inflector $inflector): void
    {
        $this->inflector = $inflector;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getType($type)
    {
        if (isset($this->typeAlias[$type])) {
            return $this->typeAlias[$type];
        }

        return $type;
    }

    /** @return string */
    protected function generateEntityNamespace(ClassMetadataInfo $metadata)
    {
        if (! $this->hasNamespace($metadata)) {
            return '';
        }

        return 'namespace ' . $this->getNamespace($metadata) . ';';
    }

    /** @return string */
    protected function generateEntityUse()
    {
        if (! $this->generateAnnotations) {
            return '';
        }

        return "\n" . 'use Doctrine\ORM\Mapping as ORM;' . "\n";
    }

    /** @return string */
    protected function generateEntityClassName(ClassMetadataInfo $metadata)
    {
        return 'class ' . $this->getClassName($metadata) .
            ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    /** @return string */
    protected function generateEntityBody(ClassMetadataInfo $metadata)
    {
        $fieldMappingProperties       = $this->generateEntityFieldMappingProperties($metadata);
        $embeddedProperties           = $this->generateEntityEmbeddedProperties($metadata);
        $associationMappingProperties = $this->generateEntityAssociationMappingProperties($metadata);
        $stubMethods                  = $this->generateEntityStubMethods ? $this->generateEntityStubMethods($metadata) : null;
        $lifecycleCallbackMethods     = $this->generateEntityLifecycleCallbackMethods($metadata);

        $code = [];

        if ($fieldMappingProperties) {
            $code[] = $fieldMappingProperties;
        }

        if ($embeddedProperties) {
            $code[] = $embeddedProperties;
        }

        if ($associationMappingProperties) {
            $code[] = $associationMappingProperties;
        }

        $code[] = $this->generateEntityConstructor($metadata);

        if ($stubMethods) {
            $code[] = $stubMethods;
        }

        if ($lifecycleCallbackMethods) {
            $code[] = $lifecycleCallbackMethods;
        }

        return implode("\n", $code);
    }

    /** @return string */
    protected function generateEntityConstructor(ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod('__construct', $metadata)) {
            return '';
        }

        if ($metadata->isEmbeddedClass && $this->embeddablesImmutable) {
            return $this->generateEmbeddableConstructor($metadata);
        }

        $collections = [];

        foreach ($metadata->associationMappings as $mapping) {
            if ($mapping['type'] & ClassMetadataInfo::TO_MANY) {
                $collections[] = '$this->' . $mapping['fieldName'] . ' = new \Doctrine\Common\Collections\ArrayCollection();';
            }
        }

        if ($collections) {
            return $this->prefixCodeWithSpaces(str_replace('<collections>', implode("\n" . $this->spaces, $collections), static::$constructorMethodTemplate));
        }

        return '';
    }

    private function generateEmbeddableConstructor(ClassMetadataInfo $metadata): string
    {
        $paramTypes     = [];
        $paramVariables = [];
        $params         = [];
        $fields         = [];

        // Resort fields to put optional fields at the end of the method signature.
        $requiredFields = [];
        $optionalFields = [];

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (empty($fieldMapping['nullable'])) {
                $requiredFields[] = $fieldMapping;

                continue;
            }

            $optionalFields[] = $fieldMapping;
        }

        $fieldMappings = array_merge($requiredFields, $optionalFields);

        foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {
            $paramType     = '\\' . ltrim($embeddedClass['class'], '\\');
            $paramVariable = '$' . $fieldName;

            $paramTypes[]     = $paramType;
            $paramVariables[] = $paramVariable;
            $params[]         = $paramType . ' ' . $paramVariable;
            $fields[]         = '$this->' . $fieldName . ' = ' . $paramVariable . ';';
        }

        foreach ($fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['declaredField'], $metadata->embeddedClasses[$fieldMapping['declaredField']])) {
                continue;
            }

            $paramTypes[]     = $this->getType($fieldMapping['type']) . (! empty($fieldMapping['nullable']) ? '|null' : '');
            $param            = '$' . $fieldMapping['fieldName'];
            $paramVariables[] = $param;

            if ($fieldMapping['type'] === 'datetime') {
                $param = $this->getType($fieldMapping['type']) . ' ' . $param;
            }

            if (! empty($fieldMapping['nullable'])) {
                $param .= ' = null';
            }

            $params[] = $param;

            $fields[] = '$this->' . $fieldMapping['fieldName'] . ' = $' . $fieldMapping['fieldName'] . ';';
        }

        $maxParamTypeLength = max(array_map('strlen', $paramTypes));
        $paramTags          = array_map(
            static function ($type, $variable) use ($maxParamTypeLength) {
                return '@param ' . $type . str_repeat(' ', $maxParamTypeLength - strlen($type) + 1) . $variable;
            },
            $paramTypes,
            $paramVariables
        );

        // Generate multi line constructor if the signature exceeds 120 characters.
        if (array_sum(array_map('strlen', $params)) + count($params) * 2 + 29 > 120) {
            $delimiter = "\n" . $this->spaces;
            $params    = $delimiter . implode(',' . $delimiter, $params) . "\n";
        } else {
            $params = implode(', ', $params);
        }

        $replacements = [
            '<paramTags>' => implode("\n * ", $paramTags),
            '<params>'    => $params,
            '<fields>'    => implode("\n" . $this->spaces, $fields),
        ];

        $constructor = str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$embeddableConstructorMethodTemplate
        );

        return $this->prefixCodeWithSpaces($constructor);
    }

    /**
     * @param string $src
     *
     * @return void
     *
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     * @psalm-suppress UndefinedConstant
     */
    protected function parseTokensInEntityFile($src)
    {
        $tokens            = token_get_all($src);
        $tokensCount       = count($tokens);
        $lastSeenNamespace = '';
        $lastSeenClass     = false;

        $inNamespace = false;
        $inClass     = false;

        for ($i = 0; $i < $tokensCount; $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($inNamespace) {
                if (in_array($token[0], [T_NS_SEPARATOR, T_STRING], true)) {
                    $lastSeenNamespace .= $token[1];
                } elseif (PHP_VERSION_ID >= 80000 && ($token[0] === T_NAME_QUALIFIED || $token[0] === T_NAME_FULLY_QUALIFIED)) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, [';', '{'], true)) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass                                              = false;
                $lastSeenClass                                        = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->staticReflection[$lastSeenClass]['properties'] = [];
                $this->staticReflection[$lastSeenClass]['methods']    = [];
            }

            if ($token[0] === T_NAMESPACE) {
                $lastSeenNamespace = '';
                $inNamespace       = true;
            } elseif ($token[0] === T_CLASS && $tokens[$i - 1][0] !== T_DOUBLE_COLON) {
                $inClass = true;
            } elseif ($token[0] === T_FUNCTION) {
                if ($tokens[$i + 2][0] === T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i + 2][1]);
                } elseif ($tokens[$i + 2] === '&' && $tokens[$i + 3][0] === T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i + 3][1]);
                }
            } elseif (in_array($token[0], [T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED], true) && $tokens[$i + 2][0] !== T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i + 2][1], 1);
            }
        }
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    protected function hasProperty($property, ClassMetadataInfo $metadata)
    {
        if ($this->extendsClass() || (! $this->isNew && class_exists($metadata->name))) {
            // don't generate property if its already on the base class.
            $reflClass = new ReflectionClass($this->getClassToExtend() ?: $metadata->name);
            if ($reflClass->hasProperty($property)) {
                return true;
            }
        }

        // check traits for existing property
        foreach ($this->getTraits($metadata) as $trait) {
            if ($trait->hasProperty($property)) {
                return true;
            }
        }

        return isset($this->staticReflection[$metadata->name]) &&
            in_array($property, $this->staticReflection[$metadata->name]['properties'], true);
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    protected function hasMethod($method, ClassMetadataInfo $metadata)
    {
        if ($this->extendsClass() || (! $this->isNew && class_exists($metadata->name))) {
            // don't generate method if its already on the base class.
            $reflClass = new ReflectionClass($this->getClassToExtend() ?: $metadata->name);

            if ($reflClass->hasMethod($method)) {
                return true;
            }
        }

        // check traits for existing method
        foreach ($this->getTraits($metadata) as $trait) {
            if ($trait->hasMethod($method)) {
                return true;
            }
        }

        return isset($this->staticReflection[$metadata->name]) &&
            in_array(strtolower($method), $this->staticReflection[$metadata->name]['methods'], true);
    }

    /**
     * @return ReflectionClass[]
     * @psalm-return array<trait-string, ReflectionClass<object>>
     *
     * @throws ReflectionException
     */
    protected function getTraits(ClassMetadataInfo $metadata)
    {
        if (! ($metadata->reflClass !== null || class_exists($metadata->name))) {
            return [];
        }

        $reflClass = $metadata->reflClass ?? new ReflectionClass($metadata->name);

        $traits = [];

        while ($reflClass !== false) {
            $traits = array_merge($traits, $reflClass->getTraits());

            $reflClass = $reflClass->getParentClass();
        }

        return $traits;
    }

    /** @return bool */
    protected function hasNamespace(ClassMetadataInfo $metadata)
    {
        return str_contains($metadata->name, '\\');
    }

    /** @return bool */
    protected function extendsClass()
    {
        return (bool) $this->classToExtend;
    }

    /** @return string */
    protected function getClassToExtend()
    {
        return $this->classToExtend;
    }

    /** @return string */
    protected function getClassToExtendName()
    {
        $refl = new ReflectionClass($this->getClassToExtend());

        return '\\' . $refl->name;
    }

    /** @return string */
    protected function getClassName(ClassMetadataInfo $metadata)
    {
        return ($pos = strrpos($metadata->name, '\\'))
            ? substr($metadata->name, $pos + 1, strlen($metadata->name)) : $metadata->name;
    }

    /** @return string */
    protected function getNamespace(ClassMetadataInfo $metadata)
    {
        return substr($metadata->name, 0, strrpos($metadata->name, '\\'));
    }

    /** @return string */
    protected function generateEntityDocBlock(ClassMetadataInfo $metadata)
    {
        $lines   = [];
        $lines[] = '/**';
        $lines[] = ' * ' . $this->getClassName($metadata);

        if ($this->generateAnnotations) {
            $lines[] = ' *';

            $methods = [
                'generateTableAnnotation',
                'generateInheritanceAnnotation',
                'generateDiscriminatorColumnAnnotation',
                'generateDiscriminatorMapAnnotation',
                'generateEntityAnnotation',
                'generateEntityListenerAnnotation',
            ];

            foreach ($methods as $method) {
                $code = $this->$method($metadata);
                if ($code) {
                    $lines[] = ' * ' . $code;
                }
            }

            if (isset($metadata->lifecycleCallbacks) && $metadata->lifecycleCallbacks) {
                $lines[] = ' * @' . $this->annotationsPrefix . 'HasLifecycleCallbacks';
            }
        }

        $lines[] = ' */';

        return implode("\n", $lines);
    }

    /** @return string */
    protected function generateEntityAnnotation(ClassMetadataInfo $metadata)
    {
        $prefix = '@' . $this->annotationsPrefix;

        if ($metadata->isEmbeddedClass) {
            return $prefix . 'Embeddable';
        }

        $customRepository = $metadata->customRepositoryClassName
            ? '(repositoryClass="' . $metadata->customRepositoryClassName . '")'
            : '';

        return $prefix . ($metadata->isMappedSuperclass ? 'MappedSuperclass' : 'Entity') . $customRepository;
    }

    /** @return string */
    protected function generateTableAnnotation(ClassMetadataInfo $metadata)
    {
        if ($metadata->isEmbeddedClass) {
            return '';
        }

        $table = [];

        if (isset($metadata->table['schema'])) {
            $table[] = 'schema="' . $metadata->table['schema'] . '"';
        }

        if (isset($metadata->table['name'])) {
            $table[] = 'name="' . $metadata->table['name'] . '"';
        }

        if (isset($metadata->table['options']) && $metadata->table['options']) {
            $table[] = 'options={' . $this->exportTableOptions($metadata->table['options']) . '}';
        }

        if (isset($metadata->table['uniqueConstraints']) && $metadata->table['uniqueConstraints']) {
            $constraints = $this->generateTableConstraints('UniqueConstraint', $metadata->table['uniqueConstraints']);
            $table[]     = 'uniqueConstraints={' . $constraints . '}';
        }

        if (isset($metadata->table['indexes']) && $metadata->table['indexes']) {
            $constraints = $this->generateTableConstraints('Index', $metadata->table['indexes']);
            $table[]     = 'indexes={' . $constraints . '}';
        }

        return '@' . $this->annotationsPrefix . 'Table(' . implode(', ', $table) . ')';
    }

    /**
     * @param string $constraintName
     * @psalm-param array<string, array<string, mixed>> $constraints
     *
     * @return string
     */
    protected function generateTableConstraints($constraintName, array $constraints)
    {
        $annotations = [];
        foreach ($constraints as $name => $constraint) {
            $columns = [];
            foreach ($constraint['columns'] as $column) {
                $columns[] = '"' . $column . '"';
            }

            $annotations[] = '@' . $this->annotationsPrefix . $constraintName . '(name="' . $name . '", columns={' . implode(', ', $columns) . '})';
        }

        return implode(', ', $annotations);
    }

    /** @return string */
    protected function generateInheritanceAnnotation(ClassMetadataInfo $metadata)
    {
        if ($metadata->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            return '';
        }

        return '@' . $this->annotationsPrefix . 'InheritanceType("' . $this->getInheritanceTypeString($metadata->inheritanceType) . '")';
    }

    /** @return string */
    protected function generateDiscriminatorColumnAnnotation(ClassMetadataInfo $metadata)
    {
        if ($metadata->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            return '';
        }

        $discrColumn = $metadata->discriminatorColumn;
        if ($discrColumn === null) {
            return '';
        }

        $columnDefinition = sprintf('name="%s", type="%s"', $discrColumn['name'], $discrColumn['type']);
        if (isset($discrColumn['length'])) {
            $columnDefinition .= ', length=' . $discrColumn['length'];
        }

        return '@' . $this->annotationsPrefix . 'DiscriminatorColumn(' . $columnDefinition . ')';
    }

    /** @return string|null */
    protected function generateDiscriminatorMapAnnotation(ClassMetadataInfo $metadata)
    {
        if ($metadata->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            return null;
        }

        $inheritanceClassMap = [];

        foreach ($metadata->discriminatorMap as $type => $class) {
            $inheritanceClassMap[] = '"' . $type . '" = "' . $class . '"';
        }

        return '@' . $this->annotationsPrefix . 'DiscriminatorMap({' . implode(', ', $inheritanceClassMap) . '})';
    }

    /** @return string */
    protected function generateEntityStubMethods(ClassMetadataInfo $metadata)
    {
        $methods = [];

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['declaredField'], $metadata->embeddedClasses[$fieldMapping['declaredField']])) {
                continue;
            }

            $nullableField = $this->nullableFieldExpression($fieldMapping);

            if (
                (! $metadata->isEmbeddedClass || ! $this->embeddablesImmutable)
                && (! isset($fieldMapping['id']) || ! $fieldMapping['id'] || $metadata->generatorType === ClassMetadataInfo::GENERATOR_TYPE_NONE)
            ) {
                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'set',
                    $fieldMapping['fieldName'],
                    $fieldMapping['type'],
                    $nullableField
                );
            }

            $methods[] = $this->generateEntityStubMethod(
                $metadata,
                'get',
                $fieldMapping['fieldName'],
                $fieldMapping['type'],
                $nullableField
            );
        }

        foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {
            if (isset($embeddedClass['declaredField'])) {
                continue;
            }

            if (! $metadata->isEmbeddedClass || ! $this->embeddablesImmutable) {
                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'set',
                    $fieldName,
                    $embeddedClass['class']
                );
            }

            $methods[] = $this->generateEntityStubMethod(
                $metadata,
                'get',
                $fieldName,
                $embeddedClass['class']
            );
        }

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $nullable  = $this->isAssociationIsNullable($associationMapping) ? 'null' : null;
                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'set',
                    $associationMapping['fieldName'],
                    $associationMapping['targetEntity'],
                    $nullable
                );

                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'get',
                    $associationMapping['fieldName'],
                    $associationMapping['targetEntity'],
                    $nullable
                );
            } elseif ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'add',
                    $associationMapping['fieldName'],
                    $associationMapping['targetEntity']
                );

                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'remove',
                    $associationMapping['fieldName'],
                    $associationMapping['targetEntity']
                );

                $methods[] = $this->generateEntityStubMethod(
                    $metadata,
                    'get',
                    $associationMapping['fieldName'],
                    Collection::class
                );
            }
        }

        return implode("\n\n", array_filter($methods));
    }

    /**
     * @psalm-param array<string, mixed> $associationMapping
     *
     * @return bool
     */
    protected function isAssociationIsNullable(array $associationMapping)
    {
        if (isset($associationMapping['id']) && $associationMapping['id']) {
            return false;
        }

        if (isset($associationMapping['joinColumns'])) {
            $joinColumns = $associationMapping['joinColumns'];
        } else {
            //@todo there is no way to retrieve targetEntity metadata
            $joinColumns = [];
        }

        foreach ($joinColumns as $joinColumn) {
            if (isset($joinColumn['nullable']) && ! $joinColumn['nullable']) {
                return false;
            }
        }

        return true;
    }

    /** @return string */
    protected function generateEntityLifecycleCallbackMethods(ClassMetadataInfo $metadata)
    {
        if (empty($metadata->lifecycleCallbacks)) {
            return '';
        }

        $methods = [];

        foreach ($metadata->lifecycleCallbacks as $name => $callbacks) {
            foreach ($callbacks as $callback) {
                $methods[] = $this->generateLifecycleCallbackMethod($name, $callback, $metadata);
            }
        }

        return implode("\n\n", array_filter($methods));
    }

    /** @return string */
    protected function generateEntityAssociationMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = [];

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($this->hasProperty($associationMapping['fieldName'], $metadata)) {
                continue;
            }

            $lines[] = $this->generateAssociationMappingPropertyDocBlock($associationMapping, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $associationMapping['fieldName']
                     . ($associationMapping['type'] === ClassMetadataInfo::MANY_TO_MANY ? ' = array()' : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    /** @return string */
    protected function generateEntityFieldMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = [];

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (
                isset($fieldMapping['declaredField'], $metadata->embeddedClasses[$fieldMapping['declaredField']]) ||
                $this->hasProperty($fieldMapping['fieldName'], $metadata) ||
                $metadata->isInheritedField($fieldMapping['fieldName'])
            ) {
                continue;
            }

            $defaultValue = '';
            if (isset($fieldMapping['options']['default'])) {
                if ($fieldMapping['type'] === 'boolean' && $fieldMapping['options']['default'] === '1') {
                    $defaultValue = ' = true';
                } elseif (($fieldMapping['type'] === 'integer' || $fieldMapping['type'] === 'float') && ! empty($fieldMapping['options']['default'])) {
                    $defaultValue = ' = ' . (string) $fieldMapping['options']['default'];
                } else {
                    $defaultValue = ' = ' . var_export($fieldMapping['options']['default'], true);
                }
            }

            $lines[] = $this->generateFieldMappingPropertyDocBlock($fieldMapping, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $fieldMapping['fieldName'] . $defaultValue . ";\n";
        }

        return implode("\n", $lines);
    }

    /** @return string */
    protected function generateEntityEmbeddedProperties(ClassMetadataInfo $metadata)
    {
        $lines = [];

        foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {
            if (isset($embeddedClass['declaredField']) || $this->hasProperty($fieldName, $metadata)) {
                continue;
            }

            $lines[] = $this->generateEmbeddedPropertyDocBlock($embeddedClass);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $fieldName . ";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * @param string      $type
     * @param string      $fieldName
     * @param string|null $typeHint
     * @param string|null $defaultValue
     *
     * @return string
     */
    protected function generateEntityStubMethod(ClassMetadataInfo $metadata, $type, $fieldName, $typeHint = null, $defaultValue = null)
    {
        $methodName   = $type . $this->inflector->classify($fieldName);
        $variableName = $this->inflector->camelize($fieldName);

        if (in_array($type, ['add', 'remove'], true)) {
            $methodName   = $this->inflector->singularize($methodName);
            $variableName = $this->inflector->singularize($variableName);
        }

        if ($this->hasMethod($methodName, $metadata)) {
            return '';
        }

        $this->staticReflection[$metadata->name]['methods'][] = strtolower($methodName);

        $var      = sprintf('%sMethodTemplate', $type);
        $template = (string) static::$$var;

        $methodTypeHint = '';
        $types          = Type::getTypesMap();
        $variableType   = $typeHint ? $this->getType($typeHint) : null;

        if ($typeHint && ! isset($types[$typeHint])) {
            $variableType   =  '\\' . ltrim($variableType, '\\');
            $methodTypeHint =  '\\' . $typeHint . ' ';
        }

        $replacements = [
            '<description>'       => ucfirst($type) . ' ' . $variableName . '.',
            '<methodTypeHint>'    => $methodTypeHint,
            '<variableType>'      => $variableType . ($defaultValue !== null ? '|' . $defaultValue : ''),
            '<variableName>'      => $variableName,
            '<methodName>'        => $methodName,
            '<fieldName>'         => $fieldName,
            '<variableDefault>'   => $defaultValue !== null ? ' = ' . $defaultValue : '',
            '<entity>'            => $this->getClassName($metadata),
        ];

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * @param string $name
     * @param string $methodName
     *
     * @return string
     */
    protected function generateLifecycleCallbackMethod($name, $methodName, ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod($methodName, $metadata)) {
            return '';
        }

        $this->staticReflection[$metadata->name]['methods'][] = $methodName;

        $replacements = [
            '<name>'        => $this->annotationsPrefix . ucfirst($name),
            '<methodName>'  => $methodName,
        ];

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$lifecycleCallbackMethodTemplate
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * @psalm-param array<string, mixed> $joinColumn
     *
     * @return string
     */
    protected function generateJoinColumnAnnotation(array $joinColumn)
    {
        $joinColumnAnnot = [];

        if (isset($joinColumn['name'])) {
            $joinColumnAnnot[] = 'name="' . $joinColumn['name'] . '"';
        }

        if (isset($joinColumn['referencedColumnName'])) {
            $joinColumnAnnot[] = 'referencedColumnName="' . $joinColumn['referencedColumnName'] . '"';
        }

        if (isset($joinColumn['unique']) && $joinColumn['unique']) {
            $joinColumnAnnot[] = 'unique=true';
        }

        if (isset($joinColumn['nullable'])) {
            $joinColumnAnnot[] = 'nullable=' . ($joinColumn['nullable'] ? 'true' : 'false');
        }

        if (isset($joinColumn['onDelete'])) {
            $joinColumnAnnot[] = 'onDelete="' . $joinColumn['onDelete'] . '"';
        }

        if (isset($joinColumn['columnDefinition'])) {
            $joinColumnAnnot[] = 'columnDefinition="' . $joinColumn['columnDefinition'] . '"';
        }

        return '@' . $this->annotationsPrefix . 'JoinColumn(' . implode(', ', $joinColumnAnnot) . ')';
    }

    /**
     * @param mixed[] $associationMapping
     *
     * @return string
     */
    protected function generateAssociationMappingPropertyDocBlock(array $associationMapping, ClassMetadataInfo $metadata)
    {
        $lines   = [];
        $lines[] = $this->spaces . '/**';

        if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
            $lines[] = $this->spaces . ' * @var \Doctrine\Common\Collections\Collection';
        } else {
            $lines[] = $this->spaces . ' * @var \\' . ltrim($associationMapping['targetEntity'], '\\');
        }

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            if (isset($associationMapping['id']) && $associationMapping['id']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';

                $generatorType = $this->getIdGeneratorTypeString($metadata->generatorType);
                if ($generatorType) {
                    $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
                }
            }

            $type = null;
            switch ($associationMapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                    $type = 'OneToOne';
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                    $type = 'ManyToOne';
                    break;
                case ClassMetadataInfo::ONE_TO_MANY:
                    $type = 'OneToMany';
                    break;
                case ClassMetadataInfo::MANY_TO_MANY:
                    $type = 'ManyToMany';
                    break;
            }

            $typeOptions = [];

            if (isset($associationMapping['targetEntity'])) {
                $typeOptions[] = 'targetEntity="' . $associationMapping['targetEntity'] . '"';
            }

            if (isset($associationMapping['inversedBy'])) {
                $typeOptions[] = 'inversedBy="' . $associationMapping['inversedBy'] . '"';
            }

            if (isset($associationMapping['mappedBy'])) {
                $typeOptions[] = 'mappedBy="' . $associationMapping['mappedBy'] . '"';
            }

            if ($associationMapping['cascade']) {
                $cascades = [];

                if ($associationMapping['isCascadePersist']) {
                    $cascades[] = '"persist"';
                }

                if ($associationMapping['isCascadeRemove']) {
                    $cascades[] = '"remove"';
                }

                if ($associationMapping['isCascadeDetach']) {
                    $cascades[] = '"detach"';
                }

                if ($associationMapping['isCascadeMerge']) {
                    $cascades[] = '"merge"';
                }

                if ($associationMapping['isCascadeRefresh']) {
                    $cascades[] = '"refresh"';
                }

                if (count($cascades) === 5) {
                    $cascades = ['"all"'];
                }

                $typeOptions[] = 'cascade={' . implode(',', $cascades) . '}';
            }

            if (isset($associationMapping['orphanRemoval']) && $associationMapping['orphanRemoval']) {
                $typeOptions[] = 'orphanRemoval=true';
            }

            if (isset($associationMapping['fetch']) && $associationMapping['fetch'] !== ClassMetadataInfo::FETCH_LAZY) {
                $fetchMap = [
                    ClassMetadataInfo::FETCH_EXTRA_LAZY => 'EXTRA_LAZY',
                    ClassMetadataInfo::FETCH_EAGER      => 'EAGER',
                ];

                $typeOptions[] = 'fetch="' . $fetchMap[$associationMapping['fetch']] . '"';
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . '' . $type . '(' . implode(', ', $typeOptions) . ')';

            if (isset($associationMapping['joinColumns']) && $associationMapping['joinColumns']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinColumns({';

                $joinColumnsLines = [];

                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $joinColumnAnnot = $this->generateJoinColumnAnnotation($joinColumn);
                    if ($joinColumnAnnot) {
                        $joinColumnsLines[] = $this->spaces . ' *   ' . $joinColumnAnnot;
                    }
                }

                $lines[] = implode(",\n", $joinColumnsLines);
                $lines[] = $this->spaces . ' * })';
            }

            if (isset($associationMapping['joinTable']) && $associationMapping['joinTable']) {
                $joinTable   = [];
                $joinTable[] = 'name="' . $associationMapping['joinTable']['name'] . '"';

                if (isset($associationMapping['joinTable']['schema'])) {
                    $joinTable[] = 'schema="' . $associationMapping['joinTable']['schema'] . '"';
                }

                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinTable(' . implode(', ', $joinTable) . ',';
                $lines[] = $this->spaces . ' *   joinColumns={';

                $joinColumnsLines = [];

                foreach ($associationMapping['joinTable']['joinColumns'] as $joinColumn) {
                    $joinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
                }

                $lines[] = implode(',' . PHP_EOL, $joinColumnsLines);
                $lines[] = $this->spaces . ' *   },';
                $lines[] = $this->spaces . ' *   inverseJoinColumns={';

                $inverseJoinColumnsLines = [];

                foreach ($associationMapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
                    $inverseJoinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
                }

                $lines[] = implode(',' . PHP_EOL, $inverseJoinColumnsLines);
                $lines[] = $this->spaces . ' *   }';
                $lines[] = $this->spaces . ' * )';
            }

            if (isset($associationMapping['orderBy'])) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'OrderBy({';

                foreach ($associationMapping['orderBy'] as $name => $direction) {
                    $lines[] = $this->spaces . ' *     "' . $name . '"="' . $direction . '",';
                }

                $lines[count($lines) - 1] = substr($lines[count($lines) - 1], 0, strlen($lines[count($lines) - 1]) - 1);
                $lines[]                  = $this->spaces . ' * })';
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * @param mixed[] $fieldMapping
     *
     * @return string
     */
    protected function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines   = [];
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var '
            . $this->getType($fieldMapping['type'])
            . ($this->nullableFieldExpression($fieldMapping) ? '|null' : '');

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $column = [];
            if (isset($fieldMapping['columnName'])) {
                $column[] = 'name="' . $fieldMapping['columnName'] . '"';
            }

            if (isset($fieldMapping['type'])) {
                $column[] = 'type="' . $fieldMapping['type'] . '"';
            }

            if (isset($fieldMapping['length'])) {
                $column[] = 'length=' . $fieldMapping['length'];
            }

            if (isset($fieldMapping['precision'])) {
                $column[] = 'precision=' . $fieldMapping['precision'];
            }

            if (isset($fieldMapping['scale'])) {
                $column[] = 'scale=' . $fieldMapping['scale'];
            }

            if (isset($fieldMapping['nullable'])) {
                $column[] = 'nullable=' . var_export($fieldMapping['nullable'], true);
            }

            $options = [];

            if (isset($fieldMapping['options']['default']) && $fieldMapping['options']['default']) {
                $options[] = '"default"="' . $fieldMapping['options']['default'] . '"';
            }

            if (isset($fieldMapping['options']['unsigned']) && $fieldMapping['options']['unsigned']) {
                $options[] = '"unsigned"=true';
            }

            if (isset($fieldMapping['options']['fixed']) && $fieldMapping['options']['fixed']) {
                $options[] = '"fixed"=true';
            }

            if (isset($fieldMapping['options']['comment']) && $fieldMapping['options']['comment']) {
                $options[] = '"comment"="' . str_replace('"', '""', (string) $fieldMapping['options']['comment']) . '"';
            }

            if (isset($fieldMapping['options']['collation']) && $fieldMapping['options']['collation']) {
                $options[] = '"collation"="' . $fieldMapping['options']['collation'] . '"';
            }

            if (isset($fieldMapping['options']['check']) && $fieldMapping['options']['check']) {
                $options[] = '"check"="' . $fieldMapping['options']['check'] . '"';
            }

            if ($options) {
                $column[] = 'options={' . implode(',', $options) . '}';
            }

            if (isset($fieldMapping['columnDefinition'])) {
                $column[] = 'columnDefinition="' . $fieldMapping['columnDefinition'] . '"';
            }

            if (isset($fieldMapping['unique'])) {
                $column[] = 'unique=' . var_export($fieldMapping['unique'], true);
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Column(' . implode(', ', $column) . ')';

            if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';

                $generatorType = $this->getIdGeneratorTypeString($metadata->generatorType);
                if ($generatorType) {
                    $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
                }

                if ($metadata->sequenceGeneratorDefinition) {
                    $sequenceGenerator = [];

                    if (isset($metadata->sequenceGeneratorDefinition['sequenceName'])) {
                        $sequenceGenerator[] = 'sequenceName="' . $metadata->sequenceGeneratorDefinition['sequenceName'] . '"';
                    }

                    if (isset($metadata->sequenceGeneratorDefinition['allocationSize'])) {
                        $sequenceGenerator[] = 'allocationSize=' . $metadata->sequenceGeneratorDefinition['allocationSize'];
                    }

                    if (isset($metadata->sequenceGeneratorDefinition['initialValue'])) {
                        $sequenceGenerator[] = 'initialValue=' . $metadata->sequenceGeneratorDefinition['initialValue'];
                    }

                    $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
                }
            }

            if (isset($fieldMapping['version']) && $fieldMapping['version']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Version';
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * @psalm-param array<string, mixed> $embeddedClass
     *
     * @return string
     */
    protected function generateEmbeddedPropertyDocBlock(array $embeddedClass)
    {
        $lines   = [];
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var \\' . ltrim($embeddedClass['class'], '\\');

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $embedded = ['class="' . $embeddedClass['class'] . '"'];

            if (isset($embeddedClass['columnPrefix'])) {
                if (is_string($embeddedClass['columnPrefix'])) {
                    $embedded[] = 'columnPrefix="' . $embeddedClass['columnPrefix'] . '"';
                } else {
                    $embedded[] = 'columnPrefix=' . var_export($embeddedClass['columnPrefix'], true);
                }
            }

            $lines[] = $this->spaces . ' * @' .
                $this->annotationsPrefix . 'Embedded(' . implode(', ', $embedded) . ')';
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    private function generateEntityListenerAnnotation(ClassMetadataInfo $metadata): string
    {
        if (count($metadata->entityListeners) === 0) {
            return '';
        }

        $processedClasses = [];
        foreach ($metadata->entityListeners as $event => $eventListeners) {
            foreach ($eventListeners as $eventListener) {
                $processedClasses[] = '"' . $eventListener['class'] . '"';
            }
        }

        return sprintf(
            '%s%s({%s})',
            '@' . $this->annotationsPrefix,
            'EntityListeners',
            implode(',', array_unique($processedClasses))
        );
    }

    /**
     * @param string $code
     * @param int    $num
     *
     * @return string
     */
    protected function prefixCodeWithSpaces($code, $num = 1)
    {
        $lines = explode("\n", $code);

        foreach ($lines as $key => $value) {
            if ($value !== '') {
                $lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param int $type The inheritance type used by the class and its subclasses.
     *
     * @return string The literal string for the inheritance type.
     *
     * @throws InvalidArgumentException When the inheritance type does not exist.
     */
    protected function getInheritanceTypeString($type)
    {
        if (! isset(static::$inheritanceTypeMap[$type])) {
            throw new InvalidArgumentException(sprintf('Invalid provided InheritanceType: %s', $type));
        }

        return static::$inheritanceTypeMap[$type];
    }

    /**
     * @param int $type The policy used for change-tracking for the mapped class.
     *
     * @return string The literal string for the change-tracking type.
     *
     * @throws InvalidArgumentException When the change-tracking type does not exist.
     */
    protected function getChangeTrackingPolicyString($type)
    {
        if (! isset(static::$changeTrackingPolicyMap[$type])) {
            throw new InvalidArgumentException(sprintf('Invalid provided ChangeTrackingPolicy: %s', $type));
        }

        return static::$changeTrackingPolicyMap[$type];
    }

    /**
     * @param int $type The generator to use for the mapped class.
     *
     * @return string The literal string for the generator type.
     *
     * @throws InvalidArgumentException When the generator type does not exist.
     */
    protected function getIdGeneratorTypeString($type)
    {
        if (! isset(static::$generatorStrategyMap[$type])) {
            throw new InvalidArgumentException(sprintf('Invalid provided IdGeneratorType: %s', $type));
        }

        return static::$generatorStrategyMap[$type];
    }

    /** @psalm-param array<string, mixed> $fieldMapping */
    private function nullableFieldExpression(array $fieldMapping): ?string
    {
        if (isset($fieldMapping['nullable']) && $fieldMapping['nullable'] === true) {
            return 'null';
        }

        return null;
    }

    /**
     * Exports (nested) option elements.
     *
     * @psalm-param array<string, mixed> $options
     */
    private function exportTableOptions(array $options): string
    {
        $optionsStr = [];

        foreach ($options as $name => $option) {
            if (is_array($option)) {
                $optionsStr[] = '"' . $name . '"={' . $this->exportTableOptions($option) . '}';
            } else {
                $optionsStr[] = '"' . $name . '"="' . (string) $option . '"';
            }
        }

        return implode(',', $optionsStr);
    }
}
