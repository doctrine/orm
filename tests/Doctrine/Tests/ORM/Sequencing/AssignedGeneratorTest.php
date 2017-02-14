<?php

namespace Doctrine\Tests\ORM\Sequencing;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Sequencing\AssignedGenerator;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\OrmTestCase;

/**
 * AssignedGeneratorTest
 *
 * @author robo
 */
class AssignedGeneratorTest extends OrmTestCase
{
    private $em;
    private $assignedGen;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->assignedGen = new AssignedGenerator;
    }

    /**
     * @dataProvider entitiesWithoutId
     */
    public function testThrowsExceptionIfIdNotAssigned($entity)
    {
        $this->expectException(ORMException::class);

        $this->assignedGen->generate($this->em, $entity);
    }

    public function entitiesWithoutId(): array
    {
        return [
            'single'    => [new AssignedSingleIdEntity()],
            'composite' => [new AssignedCompositeIdEntity()],
        ];
    }

    public function testCorrectIdGeneration()
    {
        $entity = new AssignedSingleIdEntity;
        $entity->myId = 1;
        $id = $this->assignedGen->generate($this->em, $entity);
        self::assertEquals(['myId' => 1], $id);

        $entity = new AssignedCompositeIdEntity;
        $entity->myId2 = 2;
        $entity->myId1 = 4;
        $id = $this->assignedGen->generate($this->em, $entity);
        self::assertEquals(['myId1' => 4, 'myId2' => 2], $id);
    }
}

/** @ORM\Entity */
class AssignedSingleIdEntity {
    /** @ORM\Id @ORM\Column(type="integer") */
    public $myId;
}

/** @ORM\Entity */
class AssignedCompositeIdEntity {
    /** @ORM\Id @ORM\Column(type="integer") */
    public $myId1;
    /** @ORM\Id @ORM\Column(type="integer") */
    public $myId2;
}
