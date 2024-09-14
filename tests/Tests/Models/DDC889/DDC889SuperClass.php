<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
#[ORM\MappedSuperclass]
class DDC889SuperClass
{
    /**
     * @var string
     * @Column()
     */
    #[ORM\Column]
    protected $name;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            ['fieldName' => 'name']
        );

        $metadata->isMappedSuperclass = true;
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }
}
