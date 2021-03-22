<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1476;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @Entity()
 */
class DDC1476EntityWithDefaultFieldType
{
    /**
     * @var int
     * @Id
     * @Column()
     * @GeneratedValue("NONE")
     */
    protected $id;

    /**
     * @var string
     * @column()
     */
    protected $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public static function loadMetadata(ClassMetadataInfo $metadata): void
    {
        $metadata->mapField(
            [
                'id'         => true,
                'fieldName'  => 'id',
            ]
        );
        $metadata->mapField(
            ['fieldName' => 'name']
        );

        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
    }
}
