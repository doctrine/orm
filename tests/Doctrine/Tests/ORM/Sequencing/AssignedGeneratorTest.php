<?php

namespace Doctrine\Tests\ORM\Sequencing;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\FieldMetadata;
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
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var AssignedGenerator
     */
    private $assignedGen;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->assignedGen = new AssignedGenerator;
    }

    public function testThrowsExceptionIfSingleIdNotAssigned()
    {
        $this->expectException(ORMException::class);

        $entity = new AssignedSingleIdEntity();
        $myIdMock = $this->createMock(FieldMetadata::class);
        $myIdMock->expects($this->once())
            ->method('getValue')
            ->willReturn($entity->myId);

        $this->assignedGen->generate($myIdMock, $this->em, $entity);
    }

    public function testCorrectIdGeneration()
    {
        $entity = new AssignedSingleIdEntity;
        $entity->myId = 1;
        $myIdField = $this->createMock(FieldMetadata::class);
        $myIdField->expects($this->once())
            ->method('getValue')
            ->willReturn(1);
        $id = $this->assignedGen->generate($myIdField, $this->em, $entity);
        self::assertEquals($entity->myId, $id);

        $entity = new AssignedCompositeIdEntity;
        $entity->myId1 = 4;
        $entity->myId2 = 2;
        $myId1Field = $this->createMock(FieldMetadata::class);
        $myId1Field->expects($this->once())
            ->method('getValue')
            ->willReturn($entity->myId1);
        $myId2Field = $this->createMock(FieldMetadata::class);
        $myId2Field->expects($this->once())
            ->method('getValue')
            ->willReturn($entity->myId2);
        $id = $this->assignedGen->generate($myId1Field, $this->em, $entity);
        self::assertEquals($entity->myId1, $id);
        $id = $this->assignedGen->generate($myId2Field, $this->em, $entity);
        self::assertEquals($entity->myId2, $id);
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
