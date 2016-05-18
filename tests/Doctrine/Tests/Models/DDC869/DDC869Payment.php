<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;

/**
 * @MappedSuperclass(repositoryClass = "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository")
 */
class DDC869Payment
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /** @Column(type="float") */
    protected $value;


    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->addProperty('id', Type::getType('integer'), ['id' => true]);
        $metadata->addProperty('value', Type::getType('float'));

        $metadata->isMappedSuperclass = true;

        $metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO);
    }

}
