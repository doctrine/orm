<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_auxiliary_entities")
 */
class AuxiliaryEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    public function __construct($id)
    {
        $this->id = (string)$id;
    }

    public function getId()
    {
        return $this->id;
    }
}
