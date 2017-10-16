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
        $this->_em->persist($childEntity);
        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->findEntity();
        $this->assertEquals($childEntity, $entity);
    }

    /**
     * @group non-cacheable
     */
    public function testUpdateWithCompositeKey()
    {
        $childEntity = new SingleChildClass();
        $this->_em->persist($childEntity);
        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->findEntity();
        $entity->extension = 'ext-new';
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->clear();

        $persistedEntity = $this->findEntity();
        $this->assertEquals($entity, $persistedEntity);
    }

    /**
     * @return \Doctrine\Tests\Models\CompositeKeyInheritance\JoinedChildClass
     */
    private function findEntity()
    {
        return $this->_em->find(SingleRootClass::class, ['keyPart1' => 'part-1', 'keyPart2' => 'part-2']);
    }
}
