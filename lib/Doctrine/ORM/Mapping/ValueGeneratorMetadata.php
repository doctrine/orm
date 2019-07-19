<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Sequencing\Generator;

class ValueGeneratorMetadata
{
    /** @var Property */
    protected $declaringProperty;

    /** @var string */
    protected $type;

    /** @var Generator\Generator */
    protected $generator;

    /**
     * @param mixed[] $definition
     */
    public function __construct(string $type, Generator\Generator $generator)
    {
        $this->type      = $type;
        $this->generator = $generator;
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

    public function getGenerator() : Generator\Generator
    {
        return $this->generator;
    }
}
