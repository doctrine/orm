<?php

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

class DDC889Class extends DDC889SuperClass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
    }

}
