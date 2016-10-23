<?php

namespace Doctrine\Tests\Models\DDC1476;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/**
 * @Entity()
 */
class DDC1476EntityWithDefaultFieldType
{
    /**
     * @Id
     * @Column()
     * @GeneratedValue("NONE")
     */
    protected $id;

    /** @Column() */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');
        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('name');
        $fieldMetadata->setType(Type::getType('string'));

        $metadata->addProperty($fieldMetadata);

        $metadata->setIdGeneratorType(Mapping\GeneratorType::NONE);
    }

}
