<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Class TableMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @todo guilhermeblanco Add constructor requiring tableName and optional schemaName
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class TableMetadata
{
    /** @var string|null */
    protected $schema;

    /** @var string|null */
    protected $name;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $indexes = [];

    /** @var array */
    protected $uniqueConstraints = [];

    /**
     * @return string|null
     */
    public function getSchema() : ?string
    {
        return $this->schema;
    }

    /**
     * @param string $schema
     */
    public function setSchema(string $schema) : void
    {
        $this->schema = $schema;
    }

    /**
     * @param string $name
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getQuotedQualifiedName(AbstractPlatform $platform) : string
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
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options) : void
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
    public function hasOption(string $name) : bool
    {
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addOption(string $name, $value) : void
    {
        $this->options[$name] = $value;
    }

    /**
     * @return array
     */
    public function getIndexes() : array
    {
        return $this->indexes;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getIndex(string $name) : array
    {
        return $this->indexes[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasIndex(string $name) : bool
    {
        return isset($this->indexes[$name]);
    }

    /**
     * @param array $index
     */
    public function addIndex(array $index) : void
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
    public function getUniqueConstraints() : array
    {
        return $this->uniqueConstraints;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getUniqueConstraint(string $name) : array
    {
        return $this->uniqueConstraints[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasUniqueConstraint(string $name) : bool
    {
        return isset($this->uniqueConstraints[$name]);
    }

    /**
     * @param array $constraint
     */
    public function addUniqueConstraint(array $constraint) : void
    {
        if (! isset($constraint['name'])) {
            $this->uniqueConstraints[] = $constraint;

            return;
        }

        $this->uniqueConstraints[$constraint['name']] = $constraint;
    }
}
