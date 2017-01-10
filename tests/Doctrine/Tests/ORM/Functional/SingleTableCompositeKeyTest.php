<?php
namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CompositeKeyInheritance\SingleChildClass;
use Doctrine\Tests\Models\CompositeKeyInheritance\SingleRootClass;
use Doctrine\Tests\OrmFunctionalTestCase;

class SingleTableCompositeKeyTest extends OrmFunctionalTestCase
{

    public function setUp()
    {
        $this->useModelSet('compositekeyinheritance');
        parent::setUp();

    }

    /**
     *
     */
    public function testInsertWithCompositeKey()
    {
        $childEntity = new SingleChildClass();
        $this->em->persist($childEntity);
        $this->em->flush();

        $this->em->clear();

        $entity = $this->findEntity();
        self::assertEquals($childEntity, $entity);
    }

    /**
     * @group non-cacheable
     */
    public function testUpdateWithCompositeKey()
    {
        $childEntity = new SingleChildClass();
        $this->em->persist($childEntity);
        $this->em->flush();

        $this->em->clear();

        $entity = $this->findEntity();
        $entity->extension = 'ext-new';
        $this->em->persist($entity);
        $this->em->flush();

        $this->em->clear();

        $persistedEntity = $this->findEntity();
        self::assertEquals($entity, $persistedEntity);
    }

    /**
     * @return \Doctrine\Tests\Models\CompositeKeyInheritance\JoinedChildClass
     */
    private function findEntity()
    {
        return $this->em->find(SingleRootClass::class, ['keyPart1' => 'part-1', 'keyPart2' => 'part-2']);
    }
}
