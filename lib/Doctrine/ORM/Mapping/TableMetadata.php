<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class TableMetadata
{
    /** @var string */
    protected $schema;

    /** @var string */
    protected $name;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $indexes = [];

    /** @var array */
    protected $uniqueConstraints = [];

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param string $schema
     */
    public function setSchema(string $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getQuotedQualifiedName(AbstractPlatform $platform)
    {
        if (!$this->schema) {
            return $platform->quoteIdentifier($this->name);
        }

        $separator = ( ! $platform->supportsSchemas() && $platform->canEmulateSchemas()) ? '__' : '.';

        return $platform->quoteIdentifier(sprintf('%s%s%s', $this->schema, $separator, $this->name));
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name)
    {
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addOption(string $name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getIndex(string $name)
    {
        return $this->indexes[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasIndex(string $name)
    {
        return isset($this->indexes[$name]);
    }

    /**
     * @param array $index
     */
    public function addIndex(array $index)
    {
        if (! isset($index['name'])) {
            $this->indexes[] = $index;

            return;
        }

        $this->indexes[$index['name']] = $index;
    }

    /**
     * @return array
     */
    public function getUniqueConstraints()
    {
        return $this->uniqueConstraints;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getUniqueConstraint(string $name)
    {
        return $this->uniqueConstraints[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasUniqueConstraint(string $name)
    {
        return isset($this->uniqueConstraints[$name]);
    }

    /**
     * @param array $constraint
     */
    public function addUniqueConstraint(array $constraint)
    {
        if (! isset($constraint['name'])) {
            $this->uniqueConstraints[] = $constraint;

            return;
        }

        $this->uniqueConstraints[$constraint['name']] = $constraint;
    }
}
