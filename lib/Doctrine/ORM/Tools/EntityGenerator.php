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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Types\Type;

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
     * Whether or not the current ClassMetadataInfo instance is new or old.
     *
     * @var boolean
     */
    protected $isNew = true;

    /**
     * @var array
     */
    protected $staticReflection = array();

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
     * @var boolean
     */
    protected $fieldVisibility = 'private';

    /**
     * Hash-map for handle types.
     *
     * @var array
     */
    protected $typeAlias = array(
        Type::DATETIMETZ    => '\DateTime',
        Type::DATETIME      => '\DateTime',
        Type::DATE          => '\DateTime',
        Type::TIME          => '\DateTime',
        Type::OBJECT        => '\stdClass',
        Type::BIGINT        => 'integer',
        Type::SMALLINT      => 'integer',
        Type::TEXT          => 'string',
        Type::BLOB          => 'string',
        Type::DECIMAL       => 'float',
        Type::JSON_ARRAY    => 'array',
        Type::SIMPLE_ARRAY  => 'array',
    );

    /**
     * Hash-map to handle generator types string.
     *
     * @var array
     */
    protected static $generatorStrategyMap = array(
        ClassMetadataInfo::GENERATOR_TYPE_AUTO      => 'AUTO',
        ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE  => 'SEQUENCE',
        ClassMetadataInfo::GENERATOR_TYPE_TABLE     => 'TABLE',
        ClassMetadataInfo::GENERATOR_TYPE_IDENTITY  => 'IDENTITY',
        ClassMetadataInfo::GENERATOR_TYPE_NONE      => 'NONE',
        ClassMetadataInfo::GENERATOR_TYPE_UUID      => 'UUID',
        ClassMetadataInfo::GENERATOR_TYPE_CUSTOM    => 'CUSTOM'
    );

    /**
     * Hash-map to handle the change tracking policy string.
     *
     * @var array
     */
    protected static $changeTrackingPolicyMap = array(
        ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT  => 'DEFERRED_IMPLICIT',
        ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT  => 'DEFERRED_EXPLICIT',
        ClassMetadataInfo::CHANGETRACKING_NOTIFY             => 'NOTIFY',
    );

    /**
     * Hash-map to handle the inheritance type string.
     *
     * @var array
     */
    protected static $inheritanceTypeMap = array(
        ClassMetadataInfo::INHERITANCE_TYPE_NONE            => 'NONE',
        ClassMetadataInfo::INHERITANCE_TYPE_JOINED          => 'JOINED',
        ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE    => 'SINGLE_TABLE',
        ClassMetadataInfo::INHERITANCE_TYPE_TABLE_PER_CLASS => 'TABLE_PER_CLASS',
    );

    /**
     * @var string
     */
    protected static $classTemplate =
'<?php

<namespace>

use Doctrine\ORM\Mapping as ORM;

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
 * @param <variableType>$<variableName>
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
 * @param <variableType>$<variableName>
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
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName>->removeElement($<variableName>);
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
     * Constructor.
     */
    public function __construct()
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            $this->annotationsPrefix = 'ORM\\';
        }
    }

    /**
     * Generates and writes entity classes for the given array of ClassMetadataInfo instances.
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
     * Generates and writes entity class to disk for the given ClassMetadataInfo instance.
     *
     * @param ClassMetadataInfo $metadata
     * @param string            $outputDirectory
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function writeEntityClass(ClassMetadataInfo $metadata, $outputDirectory)
    {
        $path = $outputDirectory . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->extension;
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->isNew = !file_exists($path) || (file_exists($path) && $this->regenerateEntityIfExists);

        if ( ! $this->isNew) {
            $this->parseTokensInEntityFile(file_get_contents($path));
        } else {
            $this->staticReflection[$metadata->name] = array('properties' => array(), 'methods' => array());
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
        } elseif ( ! $this->isNew && $this->updateEntityIfExists) {
            file_put_contents($path, $this->generateUpdatedEntityClass($metadata, $path));
        }
    }

    /**
     * Generates a PHP5 Doctrine 2 entity class from the given ClassMetadataInfo instance.
     *
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    public function generateEntityClass(ClassMetadataInfo $metadata)
    {
        $placeHolders = array(
            '<namespace>',
            '<entityAnnotation>',
            '<entityClassName>',
            '<entityBody>'
        );

        $replacements = array(
            $this->generateEntityNamespace($metadata),
            $this->generateEntityDocBlock($metadata),
            $this->generateEntityClassName($metadata),
            $this->generateEntityBody($metadata)
        );

        $code = str_replace($placeHolders, $replacements, self::$classTemplate);

        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generates the updated code for the given ClassMetadataInfo and entity at path.
     *
     * @param ClassMetadataInfo $metadata
     * @param string            $path
     *
     * @return string
     */
    public function generateUpdatedEntityClass(ClassMetadataInfo $metadata, $path)
    {
        $currentCode = file_get_contents($path);

        $body = $this->generateEntityBody($metadata);
        $body = str_replace('<spaces>', $this->spaces, $body);
        $last = strrpos($currentCode, '}');

        return substr($currentCode, 0, $last) . $body . (strlen($body) > 0 ? "\n" : ''). "}\n";
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
        if ($visibility !== self::FIELD_VISIBLE_PRIVATE && $visibility !== self::FIELD_VISIBLE_PROTECTED) {
            throw new \InvalidArgumentException('Invalid provided visibility (only private and protected are allowed): ' . $visibility);
        }

        $this->fieldVisibility = $visibility;
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
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityNamespace(ClassMetadataInfo $metadata)
    {
        if ($this->hasNamespace($metadata)) {
            return 'namespace ' . $this->getNamespace($metadata) .';';
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityClassName(ClassMetadataInfo $metadata)
    {
        return 'class ' . $this->getClassName($metadata) .
            ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityBody(ClassMetadataInfo $metadata)
    {
        $fieldMappingProperties = $this->generateEntityFieldMappingProperties($metadata);
        $associationMappingProperties = $this->generateEntityAssociationMappingProperties($metadata);
        $stubMethods = $this->generateEntityStubMethods ? $this->generateEntityStubMethods($metadata) : null;
        $lifecycleCallbackMethods = $this->generateEntityLifecycleCallbackMethods($metadata);

        $code = array();

        if ($fieldMappingProperties) {
            $code[] = $fieldMappingProperties;
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
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityConstructor(ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod('__construct', $metadata)) {
            return '';
        }

        $collections = array();

        foreach ($metadata->associationMappings as $mapping) {
            if ($mapping['type'] & ClassMetadataInfo::TO_MANY) {
                $collections[] = '$this->'.$mapping['fieldName'].' = new \Doctrine\Common\Collections\ArrayCollection();';
            }
        }

        if ($collections) {
            return $this->prefixCodeWithSpaces(str_replace("<collections>", implode("\n".$this->spaces, $collections), self::$constructorMethodTemplate));
        }

        return '';
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
        $lastSeenNamespace = "";
        $lastSeenClass = false;

        $inNamespace = false;
        $inClass = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
                continue;
            }

            if ($inNamespace) {
                if ($token[0] == T_NS_SEPARATOR || $token[0] == T_STRING) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, array(';', '{'))) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass = false;
                $lastSeenClass = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->staticReflection[$lastSeenClass]['properties'] = array();
                $this->staticReflection[$lastSeenClass]['methods'] = array();
            }

            if ($token[0] == T_NAMESPACE) {
                $lastSeenNamespace = "";
                $inNamespace = true;
            } elseif ($token[0] == T_CLASS) {
                $inClass = true;
            } elseif ($token[0] == T_FUNCTION) {
                if ($tokens[$i+2][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = $tokens[$i+2][1];
                } elseif ($tokens[$i+2] == "&" && $tokens[$i+3][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = $tokens[$i+3][1];
                }
            } elseif (in_array($token[0], array(T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED)) && $tokens[$i+2][0] != T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }

    /**
     * @param string            $property
     * @param ClassMetadataInfo $metadata
     *
     * @return bool
     */
    protected function hasProperty($property, ClassMetadataInfo $metadata)
    {
        if ($this->extendsClass()) {
            // don't generate property if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend());
            if ($reflClass->hasProperty($property)) {
                return true;
            }
        }

        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($property, $this->staticReflection[$metadata->name]['properties'])
        );
    }

    /**
     * @param string            $method
     * @param ClassMetadataInfo $metadata
     *
     * @return bool
     */
    protected function hasMethod($method, ClassMetadataInfo $metadata)
    {
        if ($this->extendsClass()) {
            // don't generate method if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend());

            if ($reflClass->hasMethod($method)) {
                return true;
            }
        }

        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($method, $this->staticReflection[$metadata->name]['methods'])
        );
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return bool
     */
    protected function hasNamespace(ClassMetadataInfo $metadata)
    {
        return strpos($metadata->name, '\\') ? true : false;
    }

    /**
     * @return bool
     */
    protected function extendsClass()
    {
        return $this->classToExtend ? true : false;
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
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function getClassName(ClassMetadataInfo $metadata)
    {
        return ($pos = strrpos($metadata->name, '\\'))
            ? substr($metadata->name, $pos + 1, strlen($metadata->name)) : $metadata->name;
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function getNamespace(ClassMetadataInfo $metadata)
    {
        return substr($metadata->name, 0, strrpos($metadata->name, '\\'));
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityDocBlock(ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = '/**';
        $lines[] = ' * ' . $this->getClassName($metadata);

        if ($this->generateAnnotations) {
            $lines[] = ' *';

            $methods = array(
                'generateTableAnnotation',
                'generateInheritanceAnnotation',
                'generateDiscriminatorColumnAnnotation',
                'generateDiscriminatorMapAnnotation'
            );

            foreach ($methods as $method) {
                if ($code = $this->$method($metadata)) {
                    $lines[] = ' * ' . $code;
                }
            }

            if ($metadata->isMappedSuperclass) {
                $lines[] = ' * @' . $this->annotationsPrefix . 'MappedSuperClass';
            } else {
                $lines[] = ' * @' . $this->annotationsPrefix . 'Entity';
            }

            if ($metadata->customRepositoryClassName) {
                $lines[count($lines) - 1] .= '(repositoryClass="' . $metadata->customRepositoryClassName . '")';
            }

            if (isset($metadata->lifecycleCallbacks) && $metadata->lifecycleCallbacks) {
                $lines[] = ' * @' . $this->annotationsPrefix . 'HasLifecycleCallbacks';
            }
        }

        $lines[] = ' */';

        return implode("\n", $lines);
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateTableAnnotation($metadata)
    {
        $table = array();

        if (isset($metadata->table['schema'])) {
            $table[] = 'schema="' . $metadata->table['schema'] . '"';
        }

        if (isset($metadata->table['name'])) {
            $table[] = 'name="' . $metadata->table['name'] . '"';
        }

        if (isset($metadata->table['uniqueConstraints']) && $metadata->table['uniqueConstraints']) {
            $constraints = $this->generateTableConstraints('UniqueConstraint', $metadata->table['uniqueConstraints']);
            $table[] = 'uniqueConstraints={' . $constraints . '}';
        }

        if (isset($metadata->table['indexes']) && $metadata->table['indexes']) {
            $constraints = $this->generateTableConstraints('Index', $metadata->table['indexes']);
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
    protected function generateTableConstraints($constraintName, $constraints)
    {
        $annotations = array();
        foreach ($constraints as $name => $constraint) {
            $columns = array();
            foreach ($constraint['columns'] as $column) {
                $columns[] = '"' . $column . '"';
            }
            $annotations[] = '@' . $this->annotationsPrefix . $constraintName . '(name="' . $name . '", columns={' . implode(', ', $columns) . '})';
        }
        return implode(', ', $annotations);
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateInheritanceAnnotation($metadata)
    {
        if ($metadata->inheritanceType != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            return '@' . $this->annotationsPrefix . 'InheritanceType("'.$this->getInheritanceTypeString($metadata->inheritanceType).'")';
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateDiscriminatorColumnAnnotation($metadata)
    {
        if ($metadata->inheritanceType != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            $discrColumn = $metadata->discriminatorValue;
            $columnDefinition = 'name="' . $discrColumn['name']
                . '", type="' . $discrColumn['type']
                . '", length=' . $discrColumn['length'];

            return '@' . $this->annotationsPrefix . 'DiscriminatorColumn(' . $columnDefinition . ')';
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateDiscriminatorMapAnnotation($metadata)
    {
        if ($metadata->inheritanceType != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            $inheritanceClassMap = array();

            foreach ($metadata->discriminatorMap as $type => $class) {
                $inheritanceClassMap[] .= '"' . $type . '" = "' . $class . '"';
            }

            return '@' . $this->annotationsPrefix . 'DiscriminatorMap({' . implode(', ', $inheritanceClassMap) . '})';
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityStubMethods(ClassMetadataInfo $metadata)
    {
        $methods = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if ( ! isset($fieldMapping['id']) || ! $fieldMapping['id'] || $metadata->generatorType == ClassMetadataInfo::GENERATOR_TYPE_NONE) {
                if ($code = $this->generateEntityStubMethod($metadata, 'set', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
            }

            if ($code = $this->generateEntityStubMethod($metadata, 'get', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                $methods[] = $code;
            }
        }

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $nullable = $this->isAssociationIsNullable($associationMapping) ? 'null' : null;
                if ($code = $this->generateEntityStubMethod($metadata, 'set', $associationMapping['fieldName'], $associationMapping['targetEntity'], $nullable)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod($metadata, 'get', $associationMapping['fieldName'], $associationMapping['targetEntity'])) {
                    $methods[] = $code;
                }
            } elseif ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                if ($code = $this->generateEntityStubMethod($metadata, 'add', $associationMapping['fieldName'], $associationMapping['targetEntity'])) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod($metadata, 'remove', $associationMapping['fieldName'], $associationMapping['targetEntity'])) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod($metadata, 'get', $associationMapping['fieldName'], 'Doctrine\Common\Collections\Collection')) {
                    $methods[] = $code;
                }
            }
        }

        return implode("\n\n", $methods);
    }

    /**
     * @param array $associationMapping
     *
     * @return bool
     */
    protected function isAssociationIsNullable($associationMapping)
    {
        if (isset($associationMapping['id']) && $associationMapping['id']) {
            return false;
        }

        if (isset($associationMapping['joinColumns'])) {
            $joinColumns = $associationMapping['joinColumns'];
        } else {
            //@todo there is no way to retrieve targetEntity metadata
            $joinColumns = array();
        }

        foreach ($joinColumns as $joinColumn) {
            if(isset($joinColumn['nullable']) && !$joinColumn['nullable']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityLifecycleCallbackMethods(ClassMetadataInfo $metadata)
    {
        if (isset($metadata->lifecycleCallbacks) && $metadata->lifecycleCallbacks) {
            $methods = array();

            foreach ($metadata->lifecycleCallbacks as $name => $callbacks) {
                foreach ($callbacks as $callback) {
                    if ($code = $this->generateLifecycleCallbackMethod($name, $callback, $metadata)) {
                        $methods[] = $code;
                    }
                }
            }

            return implode("\n\n", $methods);
        }

        return "";
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityAssociationMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($this->hasProperty($associationMapping['fieldName'], $metadata)) {
                continue;
            }

            $lines[] = $this->generateAssociationMappingPropertyDocBlock($associationMapping, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $associationMapping['fieldName']
                     . ($associationMapping['type'] == 'manyToMany' ? ' = array()' : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityFieldMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if ($this->hasProperty($fieldMapping['fieldName'], $metadata) ||
                $metadata->isInheritedField($fieldMapping['fieldName'])) {
                continue;
            }

            $lines[] = $this->generateFieldMappingPropertyDocBlock($fieldMapping, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $fieldMapping['fieldName']
                     . (isset($fieldMapping['default']) ? ' = ' . var_export($fieldMapping['default'], true) : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @param string            $type
     * @param string            $fieldName
     * @param string|null       $typeHint
     * @param string|null       $defaultValue
     *
     * @return string
     */
    protected function generateEntityStubMethod(ClassMetadataInfo $metadata, $type, $fieldName, $typeHint = null,  $defaultValue = null)
    {
        $methodName = $type . Inflector::classify($fieldName);
        if (in_array($type, array("add", "remove"))) {
            $methodName = Inflector::singularize($methodName);
        }

        if ($this->hasMethod($methodName, $metadata)) {
            return '';
        }
        $this->staticReflection[$metadata->name]['methods'][] = $methodName;

        $var = sprintf('%sMethodTemplate', $type);
        $template = self::$$var;

        $methodTypeHint = null;
        $types          = Type::getTypesMap();
        $variableType   = $typeHint ? $this->getType($typeHint) . ' ' : null;

        if ($typeHint && ! isset($types[$typeHint])) {
            $variableType   =  '\\' . ltrim($variableType, '\\');
            $methodTypeHint =  '\\' . $typeHint . ' ';
        }

        $replacements = array(
          '<description>'       => ucfirst($type) . ' ' . $fieldName,
          '<methodTypeHint>'    => $methodTypeHint,
          '<variableType>'      => $variableType,
          '<variableName>'      => Inflector::camelize($fieldName),
          '<methodName>'        => $methodName,
          '<fieldName>'         => $fieldName,
          '<variableDefault>'   => ($defaultValue !== null ) ? (' = '.$defaultValue) : '',
          '<entity>'            => $this->getClassName($metadata)
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * @param string            $name
     * @param string            $methodName
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateLifecycleCallbackMethod($name, $methodName, $metadata)
    {
        if ($this->hasMethod($methodName, $metadata)) {
            return '';
        }
        $this->staticReflection[$metadata->name]['methods'][] = $methodName;

        $replacements = array(
            '<name>'        => $this->annotationsPrefix . ucfirst($name),
            '<methodName>'  => $methodName,
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            self::$lifecycleCallbackMethodTemplate
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * @param array $joinColumn
     *
     * @return string
     */
    protected function generateJoinColumnAnnotation(array $joinColumn)
    {
        $joinColumnAnnot = array();

        if (isset($joinColumn['name'])) {
            $joinColumnAnnot[] = 'name="' . $joinColumn['name'] . '"';
        }

        if (isset($joinColumn['referencedColumnName'])) {
            $joinColumnAnnot[] = 'referencedColumnName="' . $joinColumn['referencedColumnName'] . '"';
        }

        if (isset($joinColumn['unique']) && $joinColumn['unique']) {
            $joinColumnAnnot[] = 'unique=' . ($joinColumn['unique'] ? 'true' : 'false');
        }

        if (isset($joinColumn['nullable'])) {
            $joinColumnAnnot[] = 'nullable=' . ($joinColumn['nullable'] ? 'true' : 'false');
        }

        if (isset($joinColumn['onDelete'])) {
            $joinColumnAnnot[] = 'onDelete="' . ($joinColumn['onDelete'] . '"');
        }

        if (isset($joinColumn['columnDefinition'])) {
            $joinColumnAnnot[] = 'columnDefinition="' . $joinColumn['columnDefinition'] . '"';
        }

        return '@' . $this->annotationsPrefix . 'JoinColumn(' . implode(', ', $joinColumnAnnot) . ')';
    }

    /**
     * @param array             $associationMapping
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateAssociationMappingPropertyDocBlock(array $associationMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
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

                if ($generatorType = $this->getIdGeneratorTypeString($metadata->generatorType)) {
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
            $typeOptions = array();

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
                $cascades = array();

                if ($associationMapping['isCascadePersist']) $cascades[] = '"persist"';
                if ($associationMapping['isCascadeRemove']) $cascades[] = '"remove"';
                if ($associationMapping['isCascadeDetach']) $cascades[] = '"detach"';
                if ($associationMapping['isCascadeMerge']) $cascades[] = '"merge"';
                if ($associationMapping['isCascadeRefresh']) $cascades[] = '"refresh"';

                if (count($cascades) === 5) {
                    $cascades = array('"all"');
                }

                $typeOptions[] = 'cascade={' . implode(',', $cascades) . '}';
            }

            if (isset($associationMapping['orphanRemoval']) && $associationMapping['orphanRemoval']) {
                $typeOptions[] = 'orphanRemoval=' . ($associationMapping['orphanRemoval'] ? 'true' : 'false');
            }

            if (isset($associationMapping['fetch']) && $associationMapping['fetch'] !== ClassMetadataInfo::FETCH_LAZY) {
                $fetchMap = array(
                    ClassMetadataInfo::FETCH_EXTRA_LAZY => 'EXTRA_LAZY',
                    ClassMetadataInfo::FETCH_EAGER      => 'EAGER',
                );

                $typeOptions[] = 'fetch="' . $fetchMap[$associationMapping['fetch']] . '"';
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . '' . $type . '(' . implode(', ', $typeOptions) . ')';

            if (isset($associationMapping['joinColumns']) && $associationMapping['joinColumns']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinColumns({';

                $joinColumnsLines = array();

                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    if ($joinColumnAnnot = $this->generateJoinColumnAnnotation($joinColumn)) {
                        $joinColumnsLines[] = $this->spaces . ' *   ' . $joinColumnAnnot;
                    }
                }

                $lines[] = implode(",\n", $joinColumnsLines);
                $lines[] = $this->spaces . ' * })';
            }

            if (isset($associationMapping['joinTable']) && $associationMapping['joinTable']) {
                $joinTable = array();
                $joinTable[] = 'name="' . $associationMapping['joinTable']['name'] . '"';

                if (isset($associationMapping['joinTable']['schema'])) {
                    $joinTable[] = 'schema="' . $associationMapping['joinTable']['schema'] . '"';
                }

                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinTable(' . implode(', ', $joinTable) . ',';
                $lines[] = $this->spaces . ' *   joinColumns={';

                $joinColumnsLines = array();

                foreach ($associationMapping['joinTable']['joinColumns'] as $joinColumn) {
                    $joinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
                }

                $lines[] = implode(",". PHP_EOL, $joinColumnsLines);
                $lines[] = $this->spaces . ' *   },';
                $lines[] = $this->spaces . ' *   inverseJoinColumns={';

                $inverseJoinColumnsLines = array();

                foreach ($associationMapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
                    $inverseJoinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
                }

                $lines[] = implode(",". PHP_EOL, $inverseJoinColumnsLines);
                $lines[] = $this->spaces . ' *   }';
                $lines[] = $this->spaces . ' * )';
            }

            if (isset($associationMapping['orderBy'])) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'OrderBy({';

                foreach ($associationMapping['orderBy'] as $name => $direction) {
                    $lines[] = $this->spaces . ' *     "' . $name . '"="' . $direction . '",';
                }

                $lines[count($lines) - 1] = substr($lines[count($lines) - 1], 0, strlen($lines[count($lines) - 1]) - 1);
                $lines[] = $this->spaces . ' * })';
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * @param array             $fieldMapping
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var ' . $this->getType($fieldMapping['type']);

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $column = array();
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
                $column[] = 'precision=' .  $fieldMapping['precision'];
            }

            if (isset($fieldMapping['scale'])) {
                $column[] = 'scale=' . $fieldMapping['scale'];
            }

            if (isset($fieldMapping['nullable'])) {
                $column[] = 'nullable=' .  var_export($fieldMapping['nullable'], true);
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

                if ($generatorType = $this->getIdGeneratorTypeString($metadata->generatorType)) {
                    $lines[] = $this->spaces.' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
                }

                if ($metadata->sequenceGeneratorDefinition) {
                    $sequenceGenerator = array();

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
     * @throws \InvalidArgumentException When the inheritance type does not exists.
     */
    protected function getInheritanceTypeString($type)
    {
        if ( ! isset(self::$inheritanceTypeMap[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid provided InheritanceType: %s', $type));
        }

        return self::$inheritanceTypeMap[$type];
    }

    /**
     * @param integer $type The policy used for change-tracking for the mapped class.
     *
     * @return string The literal string for the change-tracking type.
     *
     * @throws \InvalidArgumentException When the change-tracking type does not exists.
     */
    protected function getChangeTrackingPolicyString($type)
    {
        if ( ! isset(self::$changeTrackingPolicyMap[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid provided ChangeTrackingPolicy: %s', $type));
        }

        return self::$changeTrackingPolicyMap[$type];
    }

    /**
     * @param integer $type The generator to use for the mapped class.
     *
     * @return string The literal string for the generator type.
     *
     * @throws \InvalidArgumentException    When the generator type does not exists.
     */
    protected function getIdGeneratorTypeString($type)
    {
        if ( ! isset(self::$generatorStrategyMap[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid provided IdGeneratorType: %s', $type));
        }

        return self::$generatorStrategyMap[$type];
    }
}
