<?php


namespace Doctrine\Tests\Models\DDC3711;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Marc Pantel <pantel.m@gmail.com>
 */
class DDC3711EntityA
{
    /**
     * @var int
     */
    private $id1;

    /**
     * @var int
     */
    private $id2;

    /**
     * @var ArrayCollection
     */
    private $entityB;

    /**
     * @return mixed
     */
    public function getId1()
    {
        return $this->id1;
    }

    /**
     * @param mixed $id1
     */
    public function setId1($id1)
    {
        $this->id1 = $id1;
    }

    /**
     * @return mixed
     */
    public function getId2()
    {
        return $this->id2;
    }

    /**
     * @param mixed $id2
     */
    public function setId2($id2)
    {
        $this->id2 = $id2;
    }

    /**
     * @return ArrayCollection
     */
    public function getEntityB()
    {
        return $this->entityB;
    }

    /**
     * @param ArrayCollection $entityB
     *
     * @return DDC3711EntityA
     */
    public function addEntityB($entityB)
    {
        $this->entityB[] = $entityB;

        return $this;
    }
}
