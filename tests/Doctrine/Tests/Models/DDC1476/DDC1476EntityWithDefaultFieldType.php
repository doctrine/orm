<?php

namespace Doctrine\Tests\Models\DDC1476;

use Doctrine\DBAL\Types\Type;

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

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->addProperty('id', Type::getType('string'), ['id' => true]);
        $metadata->addProperty('name', Type::getType('string'));

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
    }

}
