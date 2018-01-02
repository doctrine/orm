<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class LocalColumnMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class LocalColumnMetadata extends ColumnMetadata
{
    /**
     * @var int|null
     */
    protected $length;

    /**
     * @var int|null
     */
    protected $scale;

    /**
     * @var int|null
     */
    protected $precision;

    /**
     * @var ValueGeneratorMetadata|null
     */
    protected $valueGenerator;

    /**
     * @return int|null
     */
    public function getLength() : ?int
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length) : void
    {
        $this->length = $length;
    }

    /**
     * @return int|null
     */
    public function getScale() : ?int
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     */
    public function setScale(int $scale) : void
    {
        $this->scale = $scale;
    }

    /**
     * @return int|null
     */
    public function getPrecision() : ?int
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     */
    public function setPrecision(int $precision) : void
    {
        $this->precision = $precision;
    }

    /**
     * @return bool
     */
    public function hasValueGenerator() : bool
    {
        return $this->valueGenerator !== null;
    }

    /**
     * @return ValueGeneratorMetadata|null
     */
    public function getValueGenerator() : ?ValueGeneratorMetadata
    {
        return $this->valueGenerator;
    }

    /**
     * @param ValueGeneratorMetadata|null $valueGenerator
     */
    public function setValueGenerator(?ValueGeneratorMetadata $valueGenerator) : void
    {
        $this->valueGenerator = $valueGenerator;
    }
}
