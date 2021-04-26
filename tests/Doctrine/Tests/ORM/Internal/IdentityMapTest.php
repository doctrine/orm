<?php

namespace Doctrine\Tests\ORM\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\IdentityMap;
use Doctrine\ORM\Internal\ObjectIdFetcher;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_keys;

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

            for ($i = 0; $i < $parameters['size']; $i++) {
                $entity                 = $this->getMockBuilder(stdClass::class)->setMockClassName($className)->getMock();
                $entity->name           = $classMetadata->name;
                $entity->rootEntityName = $classMetadata->rootEntityName;
                $entity->identifier     = [$entity->name . '_Identifier'];

                $this->entities[$className][] = $entity;
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
    }
}
