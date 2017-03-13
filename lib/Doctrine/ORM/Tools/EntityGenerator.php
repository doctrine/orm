<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;

/**
 * Generic class used to generate PHP5 entity classes from ClassMetadata instances.
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
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityGenerator
{
    /**
     * Specifies class fields should be protected.
     */
    const FIELD_VISIBLE_PROTECTED = 'protected';

    /**
     * Specifies class fields should be private.
     */
    const FIELD_VISIBLE_PRIVATE = 'private';

    /**
     * @var bool
     */
    protected $backupExisting = true;

    /**
     * The extension to use for written php files.
     *
     * @var string
     */
    protected $extension = '.php';

    /**
     * Whether or not the current ClassMetadata instance is new or old.
     *
     * @var boolean
     */
    protected $isNew = true;

    /**
     * @var array
     */
    protected $staticReflection = [];

    /**
     * Number of spaces to use for indention in generated code.
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
     * @var boolean
     */
    protected $generateAnnotations = false;

    /**
     * @var string
     */
    protected $annotationsPrefix = '';

    /**
     * Whether or not to generate sub methods.
     *
     * @var boolean
     */
    protected $generateEntityStubMethods = false;

    /**
     * Whether or not to update the entity class if it exists already.
     *
     * @var boolean
     */
    protected $updateEntityIfExists = false;

    /**
     * Whether or not to re-generate entity class if it exists already.
     *
     * @var boolean
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
     * @var boolean.
     */
    protected $embeddablesImmutable = false;

    /**
     * Hash-map for handle types.
     *
     * @var array
     */
    protected $typeAlias = [
        Type::DATETIMETZ    => '\DateTime',
        Type::DATETIME      => '\DateTime',
        Type::DATE          => '\DateTime',
        Type::TIME          => '\DateTime',
        Type::OBJECT        => '\stdClass',
        Type::INTEGER       => 'int',
        Type::BIGINT        => 'int',
        Type::SMALLINT      => 'int',
        Type::TEXT          => 'string',
        Type::BLOB          => 'string',
        Type::DECIMAL       => 'string',
        Type::JSON_ARRAY    => 'array',
        Type::SIMPLE_ARRAY  => 'array',
        Type::BOOLEAN       => 'bool',
    ];

    /**
     * @var string
     */
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

    /**
     * @var string
     */
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

    /**
     * @var string
     */
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

    /**
     * @var string
     */
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

    /**
     * @var string
     */
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

    /**
     * @var string
     */
    protected static $lifecycleCallbackMethodTemplate =
'/**
 * @<name>
 */
public function <methodName>()
{
<spaces>// Add your code here
}';

    /**
     * @var string
     */
    protected static $constructorMethodTemplate =
'/**
 * Constructor
 */
public function __construct()
{
<spaces><collections>
}
';

    /**
     * @var string
     */
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

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            $this->annotationsPrefix = 'ORM\\';
        }
    }

    /**
     * Generates and writes entity classes for the given array of ClassMetadata instances.
     *
     * @param array  $metadatas
     * @param string $outputDirectory
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
     * Generates and writes entity class to disk for the given ClassMetadata instance.
     *
     * @param ClassMetadata $metadata
     * @param string        $outputDirectory
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function writeEntityClass(ClassMetadata $metadata, $outputDirectory)
    {
        $path = $outputDirectory . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->extension;
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->isNew = ! file_exists($path) || $this->regenerateEntityIfExists;

        if ( ! $this->isNew) {
            $this->parseTokensInEntityFile(file_get_contents($path));
        } else {
            $this->staticReflection[$metadata->name] = ['properties' => [], 'methods' => []];
        }

        if ($this->backupExisting && file_exists($path)) {
            $backupPath = dirname($path) . DIRECTORY_SEPARATOR . basename($path) . "~";
            if (!copy($path, $backupPath)) {
                throw new \RuntimeException("Attempt to backup overwritten entity file but copy operation failed.");
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
     * Generates a PHP5 Doctrine 2 entity class from the given ClassMetadata instance.
     *
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    public function generateEntityClass(ClassMetadata $metadata)
    {
        $placeHolders = [
            '<namespace>',
            '<useStatement>',
            '<entityAnnotation>',
            '<entityClassName>',
            '<entityBody>'
        ];

        $replacements = [
            $this->generateEntityNamespace($metadata),
            $this->generateEntityUse(),
            $this->generateEntityDocBlock($metadata),
            $this->generateEntityClassName($metadata),
            $this->generateEntityBody($metadata)
        ];

        $code = str_replace($placeHolders, $replacements, static::$classTemplate);

        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generates the updated code for the given ClassMetadata and entity at path.
     *
     * @param ClassMetadata $metadata
     * @param string        $path
     *
     * @return string
     */
    public function generateUpdatedEntityClass(ClassMetadata $metadata, $path)
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
     * @param integer $numSpaces
     *
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces = str_repeat(' ', $numSpaces);
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
     * @param bool $visibility
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function setFieldVisibility($visibility)
    {
        if ($visibility !== static::FIELD_VISIBLE_PRIVATE && $visibility !== static::FIELD_VISIBLE_PROTECTED) {
            throw new \InvalidArgumentException('Invalid provided visibility (only private and protected are allowed): ' . $visibility);
        }

        $this->fieldVisibility = $visibility;
    }

    /**
     * Sets whether or not to generate immutable embeddables.
     *
     * @param boolean $embeddablesImmutable
     */
    public function setEmbeddablesImmutable($embeddablesImmutable)
    {
        $this->embeddablesImmutable = (boolean) $embeddablesImmutable;
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

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityNamespace(ClassMetadata $metadata)
    {
        if ($this->hasNamespace($metadata)) {
            return 'namespace ' . $this->getNamespace($metadata) .';';
        }
    }

    protected function generateEntityUse()
    {
        if ($this->generateAnnotations) {
            return "\n".'use Doctrine\ORM\Annotation as ORM;'."\n";
        } else {
            return "";
        }
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityClassName(ClassMetadata $metadata)
    {
        return 'class ' . $this->getClassName($metadata) .
            ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityBody(ClassMetadata $metadata)
    {
        $fieldMappingProperties = $this->generateEntityFieldMappingProperties($metadata);
        $embeddedProperties = $this->generateEntityEmbeddedProperties($metadata);
        $associationMappingProperties = $this->generateEntityAssociationMappingProperties($metadata);
        $stubMethods = $this->generateEntityStubMethods ? $this->generateEntityStubMethods($metadata) : null;
        $lifecycleCallbackMethods = $this->generateEntityLifecycleCallbackMethods($metadata);

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

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityConstructor(ClassMetadata $metadata)
    {
        if ($this->hasMethod('__construct', $metadata)) {
            return '';
        }

        if ($metadata->isEmbeddedClass && $this->embeddablesImmutable) {
            return $this->generateEmbeddableConstructor($metadata);
        }

        $collections = [];

        foreach ($metadata->associationMappings as $association) {
            if ($association instanceof ToManyAssociationMetadata) {
                $collections[] = sprintf('$this->%s = new \%s();', $association->getName(), ArrayCollection::class);
            }
        }

        if ($collections) {
            return $this->prefixCodeWithSpaces(str_replace("<collections>", implode("\n".$this->spaces, $collections), static::$constructorMethodTemplate));
        }

        return '';
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    private function generateEmbeddableConstructor(ClassMetadata $metadata)
    {
        $paramTypes = [];
        $paramVariables = [];
        $params = [];
        $fields = [];

        // Resort fields to put optional fields at the end of the method signature.
        $requiredFields = [];
        $optionalFields = [];

        foreach ($metadata->getProperties() as $property) {
            if (! $property->isNullable()) {
                $requiredFields[] = $property;

                continue;
            }

            $optionalFields[] = $property;
        }

        $mappings = array_merge($requiredFields, $optionalFields);

        /*foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {
            $paramType = '\\' . ltrim($embeddedClass['class'], '\\');
            $paramVariable = '$' . $fieldName;

            $paramTypes[] = $paramType;
            $paramVariables[] = $paramVariable;
            $params[] = $paramType . ' ' . $paramVariable;
            $fields[] = '$this->' . $fieldName . ' = ' . $paramVariable . ';';
        }*/

        foreach ($mappings as $property) {
            /*if (isset($fieldMapping['declaredField']) && isset($metadata->embeddedClasses[$fieldMapping['declaredField']])) {
                continue;
            }*/

            $fieldName  = $property->getName();
            $fieldType  = $property->getTypeName();
            $mappedType = $this->getType($fieldType);
            $param      = '$' . $fieldName;

            $paramTypes[] = $mappedType . ($property->isNullable() ? '|null' : '');
            $paramVariables[] = $param;

            if ($fieldType === 'datetime') {
                $param = $mappedType . ' ' . $param;
            }

            if ($property->isNullable()) {
                $param .= ' = null';
            }

            $params[] = $param;

            $fields[] = '$this->' . $fieldName . ' = $' . $fieldName . ';';
        }

        $maxParamTypeLength = max(array_map('strlen', $paramTypes));
        $paramTags = array_map(
            function ($type, $variable) use ($maxParamTypeLength) {
                return '@param ' . $type . str_repeat(' ', $maxParamTypeLength - strlen($type) + 1) . $variable;
            },
            $paramTypes,
            $paramVariables
        );

        // Generate multi line constructor if the signature exceeds 120 characters.
        if (array_sum(array_map('strlen', $params)) + count($params) * 2 + 29 > 120) {
            $delimiter = "\n" . $this->spaces;
            $params = $delimiter . implode(',' . $delimiter, $params) . "\n";
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
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     *
     * @param string $src
     *
     * @return void
     */
    protected function parseTokensInEntityFile($src)
    {
        $tokens = token_get_all($src);
        $tokensCount = count($tokens);
        $lastSeenNamespace = '';
        $lastSeenClass = false;

        $inNamespace = false;
        $inClass = false;

        for ($i = 0; $i < $tokensCount; $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($inNamespace) {
                if (in_array($token[0], [T_NS_SEPARATOR, T_STRING], true)) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, [';', '{'], true)) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass = false;
                $lastSeenClass = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->staticReflection[$lastSeenClass]['properties'] = [];
                $this->staticReflection[$lastSeenClass]['methods'] = [];
            }

            if (T_NAMESPACE === $token[0]) {
                $lastSeenNamespace = '';
                $inNamespace = true;
            } elseif (T_CLASS === $token[0] && T_DOUBLE_COLON !== $tokens[$i-1][0]) {
                $inClass = true;
            } elseif (T_FUNCTION === $token[0]) {
                if (T_STRING === $tokens[$i+2][0]) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i+2][1]);
                } elseif ($tokens[$i+2] == '&' && T_STRING === $tokens[$i+3][0]) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i+3][1]);
                }
            } elseif (in_array($token[0], [T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED], true) && T_FUNCTION !== $tokens[$i+2][0]) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }

    /**
     * @param string        $property
     * @param ClassMetadata $metadata
     *
     * @return bool
     */
    protected function hasProperty($property, ClassMetadata $metadata)
    {
        if ($this->extendsClass() || (!$this->isNew && class_exists($metadata->name))) {
            // don't generate property if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend() ?: $metadata->name);
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

        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($property, $this->staticReflection[$metadata->name]['properties'], true)
        );
    }

    /**
     * @param string        $method
     * @param ClassMetadata $metadata
     *
     * @return bool
     */
    protected function hasMethod($method, ClassMetadata $metadata)
    {
        if ($this->extendsClass() || (!$this->isNew && class_exists($metadata->name))) {
            // don't generate method if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend() ?: $metadata->name);

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

        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array(strtolower($method), $this->staticReflection[$metadata->name]['methods'], true)
        );
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function getTraits(ClassMetadata $metadata)
    {
        if (! ($metadata->reflClass !== null || class_exists($metadata->name))) {
            return [];
        }

        $reflClass = $metadata->reflClass === null
            ? new \ReflectionClass($metadata->name)
            : $metadata->reflClass;

        $traits = [];

        while ($reflClass !== false) {
            $traits = array_merge($traits, $reflClass->getTraits());

            $reflClass = $reflClass->getParentClass();
        }

        return $traits;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return bool
     */
    protected function hasNamespace(ClassMetadata $metadata)
    {
        return (bool) strpos($metadata->name, '\\');
    }

    /**
     * @return bool
     */
    protected function extendsClass()
    {
        return (bool) $this->classToExtend;
    }

    /**
     * @return string
     */
    protected function getClassToExtend()
    {
        return $this->classToExtend;
    }

    /**
     * @return string
     */
    protected function getClassToExtendName()
    {
        $refl = new \ReflectionClass($this->getClassToExtend());

        return '\\' . $refl->getName();
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function getClassName(ClassMetadata $metadata)
    {
        return ($pos = strrpos($metadata->name, '\\'))
            ? substr($metadata->name, $pos + 1, strlen($metadata->name)) : $metadata->name;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function getNamespace(ClassMetadata $metadata)
    {
        return substr($metadata->name, 0, strrpos($metadata->name, '\\'));
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityDocBlock(ClassMetadata $metadata)
    {
        $lines = [];
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
            ];

            foreach ($methods as $method) {
                if ($code = $this->$method($metadata)) {
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

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityAnnotation(ClassMetadata $metadata)
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

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateTableAnnotation(ClassMetadata $metadata)
    {
        if ($metadata->isEmbeddedClass) {
            return '';
        }

        $table = [];

        if ($metadata->table->getSchema()) {
            $table[] = 'schema="' . $metadata->table->getSchema() . '"';
        }

        if ($metadata->table->getName()) {
            $table[] = 'name="' . $metadata->table->getName() . '"';
        }

        if ($metadata->table->getOptions()) {
            $table[] = 'options={' . $this->exportTableOptions($metadata->table->getOptions()) . '}';
        }

        if ($metadata->table->getUniqueConstraints()) {
            $constraints = $this->generateTableConstraints('UniqueConstraint', $metadata->table->getUniqueConstraints());
            $table[] = 'uniqueConstraints={' . $constraints . '}';
        }

        if ($metadata->table->getIndexes()) {
            $constraints = $this->generateTableConstraints('Index', $metadata->table->getIndexes());
            $table[] = 'indexes={' . $constraints . '}';
        }

        return '@' . $this->annotationsPrefix . 'Table(' . implode(', ', $table) . ')';
    }

    /**
     * @param string $constraintName
     * @param array  $constraints
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

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateInheritanceAnnotation(ClassMetadata $metadata)
    {
        if ($metadata->inheritanceType !== InheritanceType::NONE) {
            return '@' . $this->annotationsPrefix . 'InheritanceType("'.$this->getInheritanceTypeString($metadata->inheritanceType).'")';
        }
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateDiscriminatorColumnAnnotation(ClassMetadata $metadata)
    {
        if ($metadata->inheritanceType !== InheritanceType::NONE) {
            $discrColumn = $metadata->discriminatorColumn;

            $columnDefinition = sprintf(
                'name="%s", type="%s", length=%d',
                $discrColumn->getColumnName(),
                $discrColumn->getTypeName(),
                $discrColumn->getLength()
            );

            return '@' . $this->annotationsPrefix . 'DiscriminatorColumn(' . $columnDefinition . ')';
        }
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateDiscriminatorMapAnnotation(ClassMetadata $metadata)
    {
        if ($metadata->inheritanceType !== InheritanceType::NONE) {
            $inheritanceClassMap = [];

            foreach ($metadata->discriminatorMap as $type => $class) {
                $inheritanceClassMap[] .= '"' . $type . '" = "' . $class . '"';
            }

            return '@' . $this->annotationsPrefix . 'DiscriminatorMap({' . implode(', ', $inheritanceClassMap) . '})';
        }
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityStubMethods(ClassMetadata $metadata)
    {
        $methods = [];

        foreach ($metadata->getProperties() as $fieldName => $property) {
            /*if (isset($fieldMapping['declaredField']) && isset($metadata->embeddedClasses[$fieldMapping['declaredField']])) {
                continue;
            }*/

            $fieldType = $property->getTypeName();
            $nullable  = $property->isNullable() ? 'null' : null;

            if (( ! $property->isPrimaryKey() || $metadata->generatorType == GeneratorType::NONE) &&
                ( ! $metadata->isEmbeddedClass || ! $this->embeddablesImmutable) &&
                $code = $this->generateEntityStubMethod($metadata, 'set', $fieldName, $fieldType, $nullable)) {
                $methods[] = $code;
            }

            if ($code = $this->generateEntityStubMethod($metadata, 'get', $fieldName, $fieldType, $nullable)) {
                $methods[] = $code;
            }
        }

        /*foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {
            if (isset($embeddedClass['declaredField'])) {
                continue;
            }

            if ( ! $metadata->isEmbeddedClass || ! $this->embeddablesImmutable) {
                if ($code = $this->generateEntityStubMethod($metadata, 'set', $fieldName, $embeddedClass['class'])) {
                    $methods[] = $code;
                }
            }

            if ($code = $this->generateEntityStubMethod($metadata, 'get', $fieldName, $embeddedClass['class'])) {
                $methods[] = $code;
            }
        }*/

        foreach ($metadata->associationMappings as $association) {
            if ($association instanceof ToOneAssociationMetadata) {
                $nullable = $this->isAssociationIsNullable($association) ? 'null' : null;

                if ($code = $this->generateEntityStubMethod($metadata, 'set', $association->getName(), $association->getTargetEntity(), $nullable)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod($metadata, 'get', $association->getName(), $association->getTargetEntity(), $nullable)) {
                    $methods[] = $code;
                }
            } else if ($association instanceof ToManyAssociationMetadata) {
                if ($code = $this->generateEntityStubMethod($metadata, 'add', $association->getName(), $association->getTargetEntity())) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod($metadata, 'remove', $association->getName(), $association->getTargetEntity())) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod($metadata, 'get', $association->getName(), Collection::class)) {
                    $methods[] = $code;
                }
            }
        }

        return implode("\n\n", $methods);
    }

    /**
     * @param AssociationMetadata $association
     *
     * @return bool
     */
    protected function isAssociationIsNullable(AssociationMetadata $association)
    {
        if ($association->isPrimaryKey()) {
            return false;
        }

        $joinColumns = $association instanceof ToOneAssociationMetadata
            ? $association->getJoinColumns()
            : []
        ;

        foreach ($joinColumns as $joinColumn) {
            if (! $joinColumn->isNullable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityLifecycleCallbackMethods(ClassMetadata $metadata)
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

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityAssociationMappingProperties(ClassMetadata $metadata)
    {
        $lines = [];

        foreach ($metadata->associationMappings as $association) {
            if ($this->hasProperty($association->getName(), $metadata)) {
                continue;
            }

            $lines[] = $this->generateAssociationMappingPropertyDocBlock($association, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $association->getName()
                     . ($association instanceof ManyToManyAssociationMetadata ? ' = array()' : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityFieldMappingProperties(ClassMetadata $metadata)
    {
        $lines = [];

        foreach ($metadata->getProperties() as $fieldName => $property) {
            if ($this->hasProperty($fieldName, $metadata) ||
                $metadata->isInheritedProperty($fieldName) /*||
                (
                    isset($fieldMapping['declaredField']) &&
                    isset($metadata->embeddedClasses[$fieldMapping['declaredField']])
                )*/
            ) {
                continue;
            }

            $options = $property->getOptions();

            $lines[] = $this->generateFieldMappingPropertyDocBlock($property, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $fieldName
                     . (isset($options['default']) ? ' = ' . var_export($options['default'], true) : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateEntityEmbeddedProperties(ClassMetadata $metadata)
    {
        $lines = [];

        /*foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {
            if (isset($embeddedClass['declaredField']) || $this->hasProperty($fieldName, $metadata)) {
                continue;
            }

            $lines[] = $this->generateEmbeddedPropertyDocBlock($embeddedClass);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $fieldName . ";\n";
        }*/

        return implode("\n", $lines);
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $type
     * @param string        $fieldName
     * @param string|null   $typeHint
     * @param string|null   $defaultValue
     *
     * @return string
     */
    protected function generateEntityStubMethod(ClassMetadata $metadata, $type, $fieldName, $typeHint = null, $defaultValue = null)
    {
        $methodName = $type . Inflector::classify($fieldName);
        $variableName = Inflector::camelize($fieldName);

        if (in_array($type, ["add", "remove"])) {
            $methodName = Inflector::singularize($methodName);
            $variableName = Inflector::singularize($variableName);
        }

        if ($this->hasMethod($methodName, $metadata)) {
            return '';
        }
        $this->staticReflection[$metadata->name]['methods'][] = strtolower($methodName);

        $var = sprintf('%sMethodTemplate', $type);
        $template = static::$$var;

        $methodTypeHint = null;
        $types          = Type::getTypesMap();
        $variableType   = $typeHint ? $this->getType($typeHint) : null;

        if ($typeHint && ! isset($types[$typeHint])) {
            $variableType   =  '\\' . ltrim($variableType, '\\');
            $methodTypeHint =  '\\' . $typeHint . ' ';
        }

        $replacements = [
          '<description>'       => ucfirst($type) . ' ' . $variableName . '.',
          '<methodTypeHint>'    => $methodTypeHint,
          '<variableType>'      => $variableType . (null !== $defaultValue ? ('|' . $defaultValue) : ''),
          '<variableName>'      => $variableName,
          '<methodName>'        => $methodName,
          '<fieldName>'         => $fieldName,
          '<variableDefault>'   => ($defaultValue !== null ) ? (' = ' . $defaultValue) : '',
          '<entity>'            => $this->getClassName($metadata)
        ];

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * @param string        $name
     * @param string        $methodName
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateLifecycleCallbackMethod($name, $methodName, ClassMetadata $metadata)
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
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateIdentifierAnnotation(ClassMetadata $metadata)
    {
        $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';

        if ($generatorType = $this->getIdGeneratorTypeString($metadata->generatorType)) {
            $lines[] = $this->spaces.' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
        }

        if ($metadata->generatorDefinition) {
            $generator = [];

            if (isset($metadata->generatorDefinition['sequenceName'])) {
                $generator[] = 'sequenceName="' . $metadata->generatorDefinition['sequenceName'] . '"';
            }

            if (isset($metadata->generatorDefinition['allocationSize'])) {
                $generator[] = 'allocationSize=' . $metadata->generatorDefinition['allocationSize'];
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'SequenceGenerator(' . implode(', ', $generator) . ')';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param JoinTableMetadata $joinTable
     *
     * @return string
     */
    protected function generateJoinTableAnnotation(JoinTableMetadata $joinTable)
    {
        $lines            = [];
        $joinTableAnnot   = [];
        $joinTableAnnot[] = 'name="' . $joinTable->getName() . '"';

        if (! empty($joinTable->getSchema())) {
            $joinTableAnnot[] = 'schema="' . $joinTable->getSchema() . '"';
        }

        $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinTable(' . implode(', ', $joinTableAnnot) . ',';
        $lines[] = $this->spaces . ' *   joinColumns={';

        $joinColumnsLines = [];

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $joinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
        }

        $lines[] = implode(",". PHP_EOL, $joinColumnsLines);
        $lines[] = $this->spaces . ' *   },';
        $lines[] = $this->spaces . ' *   inverseJoinColumns={';

        $inverseJoinColumnsLines = [];

        foreach ($joinTable->getInverseJoinColumns() as $joinColumn) {
            $inverseJoinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
        }

        $lines[] = implode(",". PHP_EOL, $inverseJoinColumnsLines);
        $lines[] = $this->spaces . ' *   }';
        $lines[] = $this->spaces . ' * )';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param JoinColumnMetadata $joinColumn
     *
     * @return string
     */
    protected function generateJoinColumnAnnotation(JoinColumnMetadata $joinColumn)
    {
        $joinColumnAnnot = [];

        $joinColumnAnnot[] = 'name="' . $joinColumn->getColumnName() . '"';
        $joinColumnAnnot[] = 'referencedColumnName="' . $joinColumn->getReferencedColumnName() . '"';

        if ($joinColumn->isUnique()) {
            $joinColumnAnnot[] = 'unique=true';
        }

        if (!$joinColumn->isNullable()) {
            $joinColumnAnnot[] = 'nullable=false';
        }

        if (!empty($joinColumn->getOnDelete())) {
            $joinColumnAnnot[] = 'onDelete="' . $joinColumn->getOnDelete() . '"';
        }

        if ($joinColumn->getColumnDefinition()) {
            $joinColumnAnnot[] = 'columnDefinition="' . $joinColumn->getColumnDefinition() . '"';
        }

        $options = [];

        if ($joinColumn->getOptions()) {
            foreach ($joinColumn->getOptions() as $key => $value) {
                $options[] = sprintf('"%s"=%s', $key, str_replace("'", '"', var_export($value, true)));
            }
        }

        if ($options) {
            $joinColumnAnnot[] = 'options={'.implode(',', $options).'}';
        }

        return '@' . $this->annotationsPrefix . 'JoinColumn(' . implode(', ', $joinColumnAnnot) . ')';
    }

    /**
     * @param AssociationMetadata $association
     * @param ClassMetadata       $metadata
     *
     * @return string
     */
    protected function generateAssociationMappingPropertyDocBlock(AssociationMetadata $association, ClassMetadata $metadata)
    {
        $lines = [];
        $lines[] = $this->spaces . '/**';

        if ($association instanceof ToManyAssociationMetadata) {
            $lines[] = $this->spaces . ' * @var \Doctrine\Common\Collections\Collection';
        } else {
            $lines[] = $this->spaces . ' * @var \\' . ltrim($association->getTargetEntity(), '\\');
        }

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            if ($association->isPrimaryKey()) {
                $lines[] = $this->generateIdentifierAnnotation($metadata);
            }

            $type = null;

            if ($association instanceof OneToOneAssociationMetadata) {
                $type = 'OneToOne';
            } else if ($association instanceof ManyToOneAssociationMetadata) {
                $type = 'ManyToOne';
            } else if ($association instanceof OneToManyAssociationMetadata) {
                $type = 'OneToMany';
            } else if ($association instanceof ManyToManyAssociationMetadata) {
                $type = 'ManyToMany';
            }

            $typeOptions = [];

            $typeOptions[] = 'targetEntity="' . $association->getTargetEntity() . '"';

            if ($association->getMappedBy()) {
                $typeOptions[] = 'mappedBy="' . $association->getMappedBy() . '"';
            }

            if ($association->getInversedBy()) {
                $typeOptions[] = 'inversedBy="' . $association->getInversedBy() . '"';
            }

            if ($association instanceof ToManyAssociationMetadata && $association->getIndexedBy()) {
                $typeOptions[] = 'indexBy="' . $association->getIndexedBy() . '"';
            }

            if ($association->isOrphanRemoval()) {
                $typeOptions[] = 'orphanRemoval=true';
            }

            if ($association->getCascade()) {
                $cascades = [];

                foreach (['remove', 'persist', 'refresh', 'merge', 'detach'] as $cascadeType) {
                    if (in_array($cascadeType, $association->getCascade())) {
                        $cascades[] = sprintf('"%s"', $cascadeType);
                    }
                }

                if (count($cascades) === 5) {
                    $cascades = ['"all"'];
                }

                $typeOptions[] = 'cascade={' . implode(',', $cascades) . '}';
            }

            if ($association->getFetchMode() !== FetchMode::LAZY) {
                $typeOptions[] = 'fetch="' . $association->getFetchMode() . '"';
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . '' . $type . '(' . implode(', ', $typeOptions) . ')';

            if ($association instanceof ToOneAssociationMetadata && $association->getJoinColumns()) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinColumns({';

                $joinColumnsLines = [];

                foreach ($association->getJoinColumns() as $joinColumn) {
                    if ($joinColumnAnnot = $this->generateJoinColumnAnnotation($joinColumn)) {
                        $joinColumnsLines[] = $this->spaces . ' *   ' . $joinColumnAnnot;
                    }
                }

                $lines[] = implode(",\n", $joinColumnsLines);
                $lines[] = $this->spaces . ' * })';
            }

            if ($association instanceof ToManyAssociationMetadata) {
                if ($association instanceof ManyToManyAssociationMetadata && $association->getJoinTable()) {
                    $lines[] = $this->generateJoinTableAnnotation($association->getJoinTable());
                }

                if ($association->getOrderBy()) {
                    $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'OrderBy({';
                    $orderBy = [];

                    foreach ($association->getOrderBy() as $name => $direction) {
                        $orderBy[] = $this->spaces . ' *     "' . $name . '"="' . $direction . '"';
                    }

                    $lines[] = implode(',' . PHP_EOL, $orderBy);
                    $lines[] = $this->spaces . ' * })';
                }
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * @param FieldMetadata $propertyMetadata
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateFieldMappingPropertyDocBlock(FieldMetadata $propertyMetadata, ClassMetadata $metadata)
    {
        $fieldType = $propertyMetadata->getTypeName();

        $lines = [];

        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var '
            . $this->getType($fieldType)
            . ($propertyMetadata->isNullable() ? '|null' : '');

        if ($this->generateAnnotations) {
            $column  = [];
            $lines[] = $this->spaces . ' *';

            if ($propertyMetadata->getColumnName()) {
                $column[] = 'name="' . $propertyMetadata->getColumnName() . '"';
            }

            $column[] = 'type="' . $fieldType . '"';

            if (is_int($propertyMetadata->getLength())) {
                $column[] = 'length=' . $propertyMetadata->getLength();
            }

            if (is_int($propertyMetadata->getPrecision())) {
                $column[] = 'precision=' .  $propertyMetadata->getPrecision();
            }

            if (is_int($propertyMetadata->getScale())) {
                $column[] = 'scale=' .  $propertyMetadata->getScale();
            }

            if ($propertyMetadata->isNullable()) {
                $column[] = 'nullable=' .  var_export($propertyMetadata->isNullable(), true);
            }

            if ($propertyMetadata->isUnique()) {
                $column[] = 'unique=' . var_export($propertyMetadata->isUnique(), true);
            }

            if ($propertyMetadata->getColumnDefinition()) {
                $column[] = 'columnDefinition="' . $propertyMetadata->getColumnDefinition() . '"';
            }

            $options = [];

            if ($propertyMetadata->getOptions()) {
                foreach ($propertyMetadata->getOptions() as $key => $value) {
                    $options[] = sprintf('"%s"=%s', $key, str_replace("'", '"', var_export($value, true)));
                }
            }

            if ($options) {
                $column[] = 'options={'.implode(',', $options).'}';
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Column(' . implode(', ', $column) . ')';

            if ($propertyMetadata->isPrimaryKey()) {
                $lines[] = $this->generateIdentifierAnnotation($metadata);
            }

            if ($metadata->isVersioned() && $metadata->versionProperty->getName() === $propertyMetadata->getName()) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Version';
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * @param array $embeddedClass
     *
     * @return string
     */
    protected function generateEmbeddedPropertyDocBlock(array $embeddedClass)
    {
        $lines = [];
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var \\' . ltrim($embeddedClass['class'], '\\');

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $embedded = ['class="' . $embeddedClass['class'] . '"'];

            if (isset($fieldMapping['columnPrefix'])) {
                $embedded[] = 'columnPrefix=' . var_export($embeddedClass['columnPrefix'], true);
            }

            $lines[] = $this->spaces . ' * @' .
                $this->annotationsPrefix . 'Embedded(' . implode(', ', $embedded) . ')';
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
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
            if ( ! empty($value)) {
                $lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param integer $type The inheritance type used by the class and its subclasses.
     *
     * @return string The literal string for the inheritance type.
     *
     * @throws \InvalidArgumentException When the inheritance type does not exist.
     */
    protected function getInheritanceTypeString($type)
    {
        if ( ! defined(sprintf('%s::%s', InheritanceType::class, $type))) {
            throw new \InvalidArgumentException(sprintf('Invalid provided InheritanceType: %s', $type));
        }

        return $type;
    }

    /**
     * @param integer $type The policy used for change-tracking for the mapped class.
     *
     * @return string The literal string for the change-tracking type.
     *
     * @throws \InvalidArgumentException When the change-tracking type does not exist.
     */
    protected function getChangeTrackingPolicyString($type)
    {
        if ( ! defined(sprintf('%s::%s', ChangeTrackingPolicy::class, $type))) {
            throw new \InvalidArgumentException(sprintf('Invalid provided ChangeTrackingPolicy: %s', $type));
        }

        return $type;
    }

    /**
     * @param integer $type The generator to use for the mapped class.
     *
     * @return string The literal string for the generator type.
     *
     * @throws \InvalidArgumentException When the generator type does not exist.
     */
    protected function getIdGeneratorTypeString($type)
    {
        if ( ! defined(sprintf('%s::%s', GeneratorType::class, $type))) {
            throw new \InvalidArgumentException(sprintf('Invalid provided IdGeneratorType: %s', $type));
        }

        return $type;
    }

    /**
     * Exports (nested) option elements.
     *
     * @param array $options
     *
     * @return string
     */
    private function exportTableOptions(array $options)
    {
        $optionsStr = [];

        foreach ($options as $name => $option) {
            $optionValue = is_array($option)
                ? '{' . $this->exportTableOptions($option) . '}'
                : '"' . (string) $option . '"'
            ;

            $optionsStr[] = sprintf('"%s"=%s', $name, $optionValue);
        }

        return implode(',', $optionsStr);
    }
}
