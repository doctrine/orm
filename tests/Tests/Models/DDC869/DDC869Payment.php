<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;

#[ORM\MappedSuperclass(repositoryClass: DDC869PaymentRepository::class)]
class DDC869Payment
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected $id;

    /** @var float */
    #[ORM\Column(type: 'float')]
    protected $value;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'id'         => true,
                'fieldName'  => 'id',
                'type'       => 'integer',
                'columnName' => 'id',
            ],
        );
        $metadata->mapField(
            [
                'fieldName'  => 'value',
                'type'       => 'float',
            ],
        );
        $metadata->isMappedSuperclass = true;
        $metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
    }
}
