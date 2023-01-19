<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CompositeKeyInheritance\JoinedChildClass;
use Doctrine\Tests\Models\CompositeKeyInheritance\JoinedRootClass;
use Doctrine\Tests\OrmFunctionalTestCase;

class JoinedTableCompositeKeyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('compositekeyinheritance');

        parent::setUp();
    }

    public function testInsertWithCompositeKey(): void
    {
        $childEntity = new JoinedChildClass();
        $this->_em->persist($childEntity);
        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->findEntity();
        self::assertEquals($childEntity, $entity);
    }

    /** @group non-cacheable */
    public function testUpdateWithCompositeKey(): void
    {
        $childEntity = new JoinedChildClass();
        $this->_em->persist($childEntity);
        $this->_em->flush();

        $this->_em->clear();

        $entity            = $this->findEntity();
        $entity->extension = 'ext-new';
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->clear();

        $persistedEntity = $this->findEntity();
        self::assertEquals($entity, $persistedEntity);
    }

    private function findEntity(): JoinedChildClass
    {
        return $this->_em->find(
            JoinedRootClass::class,
            ['keyPart1' => 'part-1', 'keyPart2' => 'part-2']
        );
    }
}
