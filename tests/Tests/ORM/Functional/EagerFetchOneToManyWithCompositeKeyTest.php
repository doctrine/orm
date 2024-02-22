<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\EagerFetchedCompositeOneToMany\RootEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

final class EagerFetchOneToManyWithCompositeKeyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('eager_fetched_composite_one_to_many');

        parent::setUp();
    }

    /** @ticket 11154 */
    public function testItDoesNotThrowAnExceptionWhenTriggeringALoad(): void
    {
        $a1 = new RootEntity(1, 'A');

        $this->_em->persist($a1);
        $this->_em->flush();

        $this->_em->clear();

        self::assertCount(1, $this->_em->getRepository(RootEntity::class)->findAll());
    }
}
