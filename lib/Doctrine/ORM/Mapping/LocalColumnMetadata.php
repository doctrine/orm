<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

abstract class LocalColumnMetadata extends ColumnMetadata
{
    /**
     * @var integer
     */
    protected $length;

    /**
     * @var integer
     */
    protected $scale;

    /**
     * @var integer
     */
    protected $precision;

    /**
     * @var ValueGeneratorMetadata|null
     */
    protected $valueGenerator;

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     */
    public function setScale(int $scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     */
    public function setPrecision(int $precision)
    {
        $this->precision = $precision;
    }

    public function hasValueGenerator(): bool
    {
        return $this->valueGenerator !== null;
    }

    public function getValueGenerator(): ?ValueGeneratorMetadata
    {
        return $this->valueGenerator;
    }

    public function setValueGenerator(?ValueGeneratorMetadata $valueGenerator): void
    {
        $this->valueGenerator = $valueGenerator;
    }
}
