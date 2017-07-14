<?php

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\MappedSuperclass(repositoryClass="Doctrine\Tests\Models\DDC869\DDC869PaymentRepository")
 */
class DDC869Payment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="float") */
    protected $value;

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('value');
        $fieldMetadata->setType(Type::getType('float'));

        $metadata->addProperty($fieldMetadata);

        $metadata->isMappedSuperclass = true;

        $metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
        $metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
    }
}
