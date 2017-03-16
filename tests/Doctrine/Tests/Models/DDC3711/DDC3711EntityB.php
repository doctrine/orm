<?php


namespace Doctrine\Tests\Models\DDC3711;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Marc Pantel <pantel.m@gmail.com>
 */
class DDC3711EntityB
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
    private $entityA;

    /**
     * @return int
     */
    public function getId1()
    {
        return $this->id1;
    }

    /**
     * @param int $id1
     */
    public function setId1($id1)
    {
        $this->id1 = $id1;
    }

    /**
     * @return int
     */
    public function getId2()
    {
        return $this->id2;
    }

    /**
     * @param int $id2
     */
    public function setId2($id2)
    {
        $this->id2 = $id2;
    }

    /**
     * @return ArrayCollection
     */
    public function getEntityA()
    {
        return $this->entityA;
    }

    /**
     * @param ArrayCollection $entityA
     */
    public function addEntityA($entityA)
    {
        $this->entityA[] = $entityA;
    }

}
