<?php

namespace Doctrine\Tests\Models\DDC1476;

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

    /** @column() */
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

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(
            [
           'id'         => true,
           'fieldName'  => 'id',
            ]
        );
        $metadata->mapField(
            [
           'fieldName'  => 'name',
            ]
        );

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_NONE);
    }

}
