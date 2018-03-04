<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

abstract class LocalColumnMetadata extends ColumnMetadata
{
    /** @var int|null */
    protected $length;

    /** @var int|null */
    protected $scale;

    /** @var int|null */
    protected $precision;

    /** @var ValueGeneratorMetadata|null */
    protected $valueGenerator;

    public function getLength() : ?int
    {
        return $this->length;
    }

    public function setLength(int $length) : void
    {
        $this->length = $length;
    }

    public function getScale() : ?int
    {
        return $this->scale;
    }

    public function setScale(int $scale) : void
    {
        $this->scale = $scale;
    }

    public function getPrecision() : ?int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision) : void
    {
        $this->precision = $precision;
    }

    public function hasValueGenerator() : bool
    {
        return $this->valueGenerator !== null;
    }

    public function getValueGenerator() : ?ValueGeneratorMetadata
    {
        return $this->valueGenerator;
    }

    public function setValueGenerator(?ValueGeneratorMetadata $valueGenerator) : void
    {
        $this->valueGenerator = $valueGenerator;
    }
}
