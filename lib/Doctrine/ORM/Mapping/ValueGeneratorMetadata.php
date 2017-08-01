<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class ValueGeneratorMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ValueGeneratorMetadata
{
    /** @var string */
    protected $type;

    /** @var array<string, mixed> */
    protected $definition;

    /**
     * ValueGeneratorMetadata constructor.
     *
     * @param string $type
     * @param array  $definition
     */
    public function __construct(string $type, array $definition = [])
    {
        $this->type = $type;
        $this->definition = $definition;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }
}
