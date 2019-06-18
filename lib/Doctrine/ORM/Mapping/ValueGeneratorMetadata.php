<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Sequencing;

class ValueGeneratorMetadata
{
    /** @var Property */
    protected $declaringProperty;

    /** @var string */
    protected $type;

    /** @var mixed[] */
    protected $definition;

    /**
     * @param mixed[] $definition
     */
    public function __construct(string $type, array $definition = [])
    {
        $this->type       = $type;
        $this->definition = $definition;
    }

    public function getDeclaringProperty() : Property
    {
        return $this->declaringProperty;
    }

    public function setDeclaringProperty(Property $declaringProperty) : void
    {
        $this->declaringProperty = $declaringProperty;
    }

    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return mixed[]
     */
    public function getDefinition() : array
    {
        return $this->definition;
    }

    /**
     * @throws DBALException
     */
    public function getSequencingGenerator(AbstractPlatform $platform) : Sequencing\Generator
    {
        $class = $this->declaringProperty->getDeclaringClass();

        switch ($this->type) {
            case GeneratorType::IDENTITY:
                $sequenceName = null;

                // Platforms that do not have native IDENTITY support need a sequence to emulate this behaviour.
                if ($platform->usesSequenceEmulatedIdentityColumns()) {
                    $sequencePrefix = $platform->getSequencePrefix($class->getTableName(), $class->getSchemaName());
                    $idSequenceName = $platform->getIdentitySequenceName($sequencePrefix, $this->declaringProperty->getColumnName());
                    $sequenceName   = $platform->quoteIdentifier($platform->fixSchemaElementName($idSequenceName));
                }

                return $this->declaringProperty->getTypeName() === 'bigint'
                    ? new Sequencing\BigIntegerIdentityGenerator($sequenceName)
                    : new Sequencing\IdentityGenerator($sequenceName);
            case GeneratorType::SEQUENCE:
                return new Sequencing\SequenceGenerator(
                    $platform->quoteIdentifier($this->definition['sequenceName']),
                    $this->definition['allocationSize']
                );
            case GeneratorType::CUSTOM:
                $class = $this->definition['class'];

                return new $class();
        }
    }
}
