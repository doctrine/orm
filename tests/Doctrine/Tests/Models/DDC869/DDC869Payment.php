<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\Mapping as ORM;

/**
 * @MappedSuperclass(repositoryClass = "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository")
 */
#[ORM\MappedSuperclass(repositoryClass: DDC869PaymentRepository::class)]
class DDC869Payment
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    #[ORM\Id, ORM\Column(type: "integer"), ORM\GeneratedValue]
    protected $id;

    /** @Column(type="float") */
    #[ORM\Column(type: "float")]
    protected $value;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(
            [
           'id'         => true,
           'fieldName'  => 'id',
           'type'       => 'integer',
           'columnName' => 'id',
            ]
        );
        $metadata->mapField(
            [
           'fieldName'  => 'value',
           'type'       => 'float',
            ]
        );
        $metadata->isMappedSuperclass = true;
        $metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_AUTO);
    }

}
