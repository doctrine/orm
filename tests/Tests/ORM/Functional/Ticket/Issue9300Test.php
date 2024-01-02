<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\Issue9300\Issue9300Child;
use Doctrine\Tests\Models\Issue9300\Issue9300Parent;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-9300')]
class Issue9300Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('issue9300');

        parent::setUp();
    }

    public function testPersistedCollectionIsPresentInOriginalDataAfterFlush(): void
    {
        $parent = new Issue9300Parent();
        $child  = new Issue9300Child();
        $child->parents->add($parent);

        $parent->name = 'abc';
        $child->name  = 'abc';

        $this->_em->persist($parent);
        $this->_em->persist($child);
        $this->_em->flush();

        $parent->name = 'abcd';
        $child->name  = 'abcd';

        $this->_em->flush();

        self::assertArrayHasKey('parents', $this->_em->getUnitOfWork()->getOriginalEntityData($child));
    }

    public function testPersistingCollectionAfterFlushWorksAsExpected(): void
    {
        $parentOne = new Issue9300Parent();
        $parentTwo = new Issue9300Parent();
        $childOne  = new Issue9300Child();

        $parentOne->name   = 'abc';
        $parentTwo->name   = 'abc';
        $childOne->name    = 'abc';
        $childOne->parents = new ArrayCollection([$parentOne]);

        $this->_em->persist($parentOne);
        $this->_em->persist($parentTwo);
        $this->_em->persist($childOne);
        $this->_em->flush();

        // Recalculate change-set -> new original data
        $childOne->name = 'abcd';
        $this->_em->flush();

        $childOne->parents = new ArrayCollection([$parentTwo]);

        $this->_em->flush();
        $this->_em->clear();

        $childOneFresh = $this->_em->find(Issue9300Child::class, $childOne->id);
        self::assertCount(1, $childOneFresh->parents);
        self::assertEquals($parentTwo->id, $childOneFresh->parents[0]->id);
    }
}
