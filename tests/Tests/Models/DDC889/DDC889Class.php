<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

class DDC889Class extends DDC889SuperClass
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'id'         => true,
                'fieldName'  => 'id',
                'type'       => 'integer',
                'columnName' => 'id',
            ]
        );

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
    }
}
