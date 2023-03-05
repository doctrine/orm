<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CompositeKeyInheritance\SingleChildClass;
use Doctrine\Tests\Models\CompositeKeyInheritance\SingleRootClass;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class SingleTableCompositeKeyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('compositekeyinheritance');

        parent::setUp();
    }

    public function testInsertWithCompositeKey(): void
    {
        $childEntity = new SingleChildClass();
        $this->_em->persist($childEntity);
        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->findEntity();
        self::assertEquals($childEntity, $entity);
    }

    #[Group('non-cacheable')]
    public function testUpdateWithCompositeKey(): void
    {
        $childEntity = new SingleChildClass();
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

    private function findEntity(): SingleChildClass
    {
        return $this->_em->find(SingleRootClass::class, ['keyPart1' => 'part-1', 'keyPart2' => 'part-2']);
    }
}
