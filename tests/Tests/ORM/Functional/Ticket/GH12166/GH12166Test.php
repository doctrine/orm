<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12166;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH12166Test extends OrmFunctionalTestCase
{
    public function testProxyWithReadonlyIdIsNotInitializedImmediately(): void
    {
        $this->createSchemaForModels(LazyEntityWithReadonlyId::class);
        $this->_em->persist(new LazyEntityWithReadonlyId(123, 'Test Name'));
        $this->_em->flush();
        $this->_em->clear();
        $proxy = $this->_em->getReference(LazyEntityWithReadonlyId::class, 123);

        $reflClass = $this->_em->getClassMetadata(LazyEntityWithReadonlyId::class)->reflClass;
        self::assertTrue(
            $this->isUninitializedObject($proxy),
            'Proxy should remain uninitialized after creation',
        );

        $id = $proxy->getId();
        self::assertSame(123, $id);
        self::assertTrue(
            $this->isUninitializedObject($proxy),
            'Proxy should remain uninitialized after accessing ID',
        );
    }
}
