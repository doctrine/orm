<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use function sprintf;

/**
 * @todo guilhermeblanco Add constructor requiring tableName and optional schemaName
 */
class TableMetadata
{
    /** @var string|null */
    protected $schema;

    /** @var string|null */
    protected $name;

    /** @var mixed[] */
    protected $options = [];

    /** @var mixed[][] */
    protected $indexes = [];

    /** @var mixed[][] */
    protected $uniqueConstraints = [];

    public function __construct(?string $name = null, ?string $schema = null)
    {
        $this->name   = $name;
        $this->schema = $schema;
    }

    public function getSchema() : ?string
    {
        return $this->schema;
    }

    public function setSchema(string $schema) : void
    {
        $this->schema = $schema;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function getQuotedQualifiedName(AbstractPlatform $platform) : string
    {
        if (! $this->schema) {
            return $platform->quoteIdentifier($this->name);
        }

        $separator = ! $platform->supportsSchemas() && $platform->canEmulateSchemas() ? '__' : '.';

        return $platform->quoteIdentifier(sprintf('%s%s%s', $this->schema, $separator, $this->name));
    }

    /**
     * @return mixed[]
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(array $options) : void
    {
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    public function hasOption(string $name) : bool
    {
        return isset($this->options[$name]);
    }

    /**
     * @param mixed $value
     */
    public function addOption(string $name, $value) : void
    {
        $this->options[$name] = $value;
    }

    /**
     * @return mixed[][]
     */
    public function getIndexes() : array
    {
        return $this->indexes;
    }

    /**
     * @return mixed[]
     */
    public function getIndex(string $name) : array
    {
        return $this->indexes[$name];
    }

    public function hasIndex(string $name) : bool
    {
        return isset($this->indexes[$name]);
    }

    /**
     * @param mixed[] $index
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
     * @return mixed[][]
     */
    public function getUniqueConstraints() : array
    {
        return $this->uniqueConstraints;
    }

    /**
     * @return mixed[]
     */
    public function getUniqueConstraint(string $name) : array
    {
        return $this->uniqueConstraints[$name];
    }

    public function hasUniqueConstraint(string $name) : bool
    {
        return isset($this->uniqueConstraints[$name]);
    }

    /**
     * @param mixed[] $constraint
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
