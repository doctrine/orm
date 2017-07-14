<?php

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 */
class DDC5934BaseContract
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue()
     */
    public $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="DDC5934Member", fetch="LAZY", inversedBy="contracts")
     */
    public $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('id');
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $association = new Mapping\ManyToManyAssociationMetadata('members');

        $association->setTargetEntity('DDC5934Member');

        $metadata->addProperty($association);

        $metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
    }
}
