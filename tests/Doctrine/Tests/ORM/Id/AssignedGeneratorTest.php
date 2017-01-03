<?php

namespace Doctrine\Tests\ORM\Id;

use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit_Framework_MockObject_MockBuilder as MockBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use ReflectionProperty;
use Doctrine\Tests\ORM\Id\AssignedCompositeIdEntity;
use Doctrine\Tests\ORM\Id\AssignedSingleIdEntity;
use Exception;
use Doctrine\ORM\Mapping\MappingException;

/**
 * AssignedGeneratorTest
 *
 * @author robo
 */
class AssignedGeneratorTest extends OrmTestCase
{
    private $_em;
    private $_emMock;
    private $_assignedGen;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
        $this->_assignedGen = new AssignedGenerator;

        /* @var $metadatas ClassMetadata[] */
        $metadatas = array();

        foreach ([
            AssignedSingleIdEntity::class => ['myId'],
            AssignedCompositeIdEntity::class => ['myId1', 'myId2'],
        ] as $entityClass => $fieldNames) {

            $classMetadata = new ClassMetadata($entityClass);

            $entityReflection = new ReflectionClass($entityClass);

            foreach ($fieldNames as $fieldName) {
                $classMetadata->fieldMappings[$fieldName] = [
                    'columnName' => $fieldName,
                    'fieldName' => $fieldName,
                    'type' => "string"
                ];
                $classMetadata->reflFields[$fieldName] = $entityReflection->getProperty($fieldName);
                $classMetadata->fieldNames[$fieldName] = $fieldName;
            }

            $classMetadata->setIdentifier($fieldNames);

            $metadatas[$entityClass] = $classMetadata;
        }

        /* @var $entityManagerMock EntityManagerInterface */
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $entityManagerMock->method('getClassMetadata')->will($this->returnCallback(
            function ($entityClass) use ($metadatas) {
                if (!isset($metadatas[$entityClass])) {
                    throw MappingException::classIsNotAValidEntityOrMappedSuperClass($entityClass);
                }

                return $metadatas[$entityClass];
            }
        ));

        $this->_emMock = $entityManagerMock;
    }

    /**
     * @dataProvider provideTestDataForExceptionIfIdNotAssigned
     */
    public function testThrowsExceptionIfIdNotAssigned(
        $useEntityManagerMock,
        $entity
    ) {
        /* @var $em EntityManagerInterface */
        $em = $this->_em;
        if ($useEntityManagerMock) {
            $em = $this->_emMock;
        }
        try {
            $this->_assignedGen->generateId($em, $entity);
            $this->fail('Assigned generator did not throw exception even though ID was missing.');
        } catch (ORMException $expected) {}
    }

    /**
     * @dataProvider provideTestDataForCorrectIdGeneration
     */
    public function testCorrectIdGeneration(
        $useEntityManagerMock,
        $entity,
        $expectedId
    ) {
        /* @var $em EntityManagerInterface */
        $em = $this->_em;
        if ($useEntityManagerMock) {
            $em = $this->_emMock;
        }
        $id = $this->_assignedGen->generateId($em, $entity);
        $this->assertEquals($expectedId, $id);
    }

    /**
     * @return array
     */
    public function provideTestDataForExceptionIfIdNotAssigned()
    {
        if (is_null($this->_em)) {
            $this->setUp();
        }

        return [
            [
                false,
                new AssignedSingleIdEntity,
            ],
            [
                false,
                new AssignedCompositeIdEntity,
            ],
            [
                true,
                new AssignedSingleIdEntity,
            ],
            [
                true,
                new AssignedCompositeIdEntity,
            ],
        ];
    }

    /**
     * @return array
     */
    public function provideTestDataForCorrectIdGeneration()
    {
        if (is_null($this->_em)) {
            $this->setUp();
        }

        return [
            [
                false,
                new AssignedSingleIdEntity(1),
                ['myId' => 1],
            ],
            [
                false,
                new AssignedCompositeIdEntity(4, 2),
                ['myId1' => 4, 'myId2' => 2],
            ],
            [
                true,
                new AssignedSingleIdEntity(1),
                ['myId' => 1],
            ],
            [
                true,
                new AssignedCompositeIdEntity(4, 2),
                ['myId1' => 4, 'myId2' => 2],
            ],
        ];
    }
}

/** @Entity */
class AssignedSingleIdEntity {
    /** @Id @Column(type="integer") */
    public $myId;

    function __construct($myId = null)
    {
        $this->myId = $myId;
    }
}

/** @Entity */
class AssignedCompositeIdEntity {
    /** @Id @Column(type="integer") */
    public $myId1;
    /** @Id @Column(type="integer") */
    public $myId2;

    function __construct($myId1 = null, $myId2 = null)
    {
        $this->myId1 = $myId1;
        $this->myId2 = $myId2;
    }
}
