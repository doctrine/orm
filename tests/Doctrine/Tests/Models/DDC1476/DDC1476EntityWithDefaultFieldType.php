<?php

namespace Doctrine\Tests\Models\DDC1476;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity()
 */
#[ORM\Entity]
class DDC1476EntityWithDefaultFieldType
{
    /**
     * @Id
     * @Column()
     * @GeneratedValue("NONE")
     */
    #[ORM\Id, ORM\Column, ORM\GeneratedValue(strategy: "NONE")]
    protected $id;

    /** @column() */
    #[ORM\Column]
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
