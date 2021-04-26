<?php

namespace Doctrine\Tests\ORM\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\IdentityMap;
use Doctrine\ORM\Internal\ObjectIdFetcher;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_keys;
use function get_class;
use function implode;

class IdentityMapTest extends TestCase
{
    /** @var object[] */
    private array $entities = [];

    /** @var ClassMetadata[] */
    private array $entitiesClassMetadata = [];

    private EntityManagerInterface $entityManagerMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $entityConfig = ['Foo' => ['size' => 3], 'Bar' => ['size' => 2], 'Baz' => ['size' => 2, 'rootEntityName' => 'Bar']];

        foreach ($entityConfig as $className => $parameters) {
            $classMetadata                 = $this->createMock(ClassMetadata::class);
            $classMetadata->name           = $className;
            $classMetadata->rootEntityName = $parameters['rootEntityName'] ?? $className;

            $this->entitiesClassMetadata[$className] = $classMetadata;

            for ($i = 1; $i <= $parameters['size']; $i++) {
                $entity                 = $this->getMockBuilder(stdClass::class)->setMockClassName($className)->getMock();
                $entity->name           = implode('_', [$classMetadata->name, 'Entity', $i]);
                $entity->rootEntityName = $classMetadata->rootEntityName;
                $entity->identifier     = [implode('_', [$className, 'Identifier', $i])];

                $this->entities[$className][$entity->name] = $entity;
            }
        }

        $returnMap = [];
        foreach (array_keys($entityConfig) as $className) {
            $returnMap[] = [$className, $this->entitiesClassMetadata[$className]];
        }

        $this->entityManagerMock->method('getClassMetadata')->willReturnMap($returnMap);
    }

    public function testIdentityMapAddRemove(): void
    {
        $identityMap = new IdentityMap($this->entityManagerMock);
        foreach ($this->entities as $entities) {
            foreach ($entities as $entity) {
                $oid = ObjectIdFetcher::fetchObjectId($entity);
                $identityMap->addEntityIdentifier($oid, $entity->identifier);
                $this->assertTrue($identityMap->hasEntityIdentifier($oid));

                $identityMap->addToIdentityMap($entity);
                $this->assertTrue($identityMap->isInIdentityMap($entity));
            }
        }

        //Removing regular entity
        $barEntity2 = $this->entities['Bar']['Bar_Entity_2'];
        unset($this->entities['Bar']['Bar_Entity_2']);
        $identityMap->removeFromIdentityMap($barEntity2);
        $this->assertFalse($identityMap->isInIdentityMap($barEntity2));
        $this->assertEntitiesInIdentityMap($identityMap);
        $identityMap->unsetEntityIdentifier(ObjectIdFetcher::fetchObjectId($barEntity2));
        $this->assertFalse($identityMap->hasEntityIdentifier(ObjectIdFetcher::fetchObjectId($barEntity2)));

        //Removing an entity that has parent entity
        $barEntity2 = $this->entities['Baz']['Baz_Entity_1'];
        unset($this->entities['Baz']['Baz_Entity_1']);
        $identityMap->removeFromIdentityMap($barEntity2);
        $this->assertFalse($identityMap->isInIdentityMap($barEntity2));
        $this->assertEntitiesInIdentityMap($identityMap);
        $identityMap->unsetEntityIdentifier(ObjectIdFetcher::fetchObjectId($barEntity2));
        $this->assertFalse($identityMap->hasEntityIdentifier(ObjectIdFetcher::fetchObjectId($barEntity2)));
    }

    public function testFailAddToIdentityMap(): void
    {
        $entity = new \StdClass();
        $this->expectException(ORMInvalidArgumentException::class);
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(get_class($entity))->willReturn($this->createMock(ClassMetadata::class));
        $identityMap = new IdentityMap($entityManagerMock);
        $identityMap->addToIdentityMap($entity);
    }

    public function testCheckingEntitiesInIdentityMap(): void
    {
        $entity            = new \StdClass();
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(get_class($entity))->willReturn($this->createMock(ClassMetadata::class));
        $identityMap = new IdentityMap($entityManagerMock);
        $this->assertFalse($identityMap->isInIdentityMap($entity));
        $identityMap->addEntityIdentifier(ObjectIdFetcher::fetchObjectId($entity), ['identifier']);
        $identityMap->addToIdentityMap($entity);
        $this->assertTrue($identityMap->isInIdentityMap($entity));
    }

    public function testClearingIdentityMap(): void
    {
        $identityMap = new IdentityMap($this->entityManagerMock);
        foreach ($this->entities as $entities) {
            foreach ($entities as $entity) {
                $oid = ObjectIdFetcher::fetchObjectId($entity);
                $identityMap->addEntityIdentifier($oid, $entity->identifier);
                $identityMap->addToIdentityMap($entity);
            }
        }

        $identityMap->clear();

        $this->assertEmpty($identityMap);
    }

    private function assertEntitiesInIdentityMap(IdentityMap $identityMap): void
    {
        foreach ($this->entities as $entities) {
            foreach ($entities as $entity) {
                $oid = ObjectIdFetcher::fetchObjectId($entity);
                $this->assertTrue($identityMap->hasEntityIdentifier($oid));
                $this->assertTrue($identityMap->isInIdentityMap($entity));
            }
        }
    }
}
