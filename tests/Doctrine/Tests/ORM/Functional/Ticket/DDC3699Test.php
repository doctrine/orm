<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3699\DDC3699Child;
use Doctrine\Tests\Models\DDC3699\DDC3699RelationMany;
use Doctrine\Tests\Models\DDC3699\DDC3699RelationOne;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

/** @group DDC-3699 */
class DDC3699Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('ddc3699');

        parent::setUp();
    }

    /** @group DDC-3699 */
    public function testMergingParentClassFieldsDoesNotStopMergingScalarFieldsForToOneUninitializedAssociations(): void
    {
        $id = 1;

        $child = new DDC3699Child();

        $child->id          = $id;
        $child->childField  = 'childValue';
        $child->parentField = 'parentValue';

        $relation = new DDC3699RelationOne();

        $relation->id       = $id;
        $relation->child    = $child;
        $child->oneRelation = $relation;

        $this->_em->persist($relation);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $unManagedChild = $this->_em->find(DDC3699Child::class, $id);
        assert($unManagedChild instanceof DDC3699Child);

        $this->_em->detach($unManagedChild);

        // make it managed again
        $this->_em->find(DDC3699Child::class, $id);

        $unManagedChild->childField  = 'modifiedChildValue';
        $unManagedChild->parentField = 'modifiedParentValue';

        $mergedChild = $this->_em->merge($unManagedChild);
        assert($mergedChild instanceof DDC3699Child);

        self::assertSame($mergedChild->childField, 'modifiedChildValue');
        self::assertSame($mergedChild->parentField, 'modifiedParentValue');
    }

    /** @group DDC-3699 */
    public function testMergingParentClassFieldsDoesNotStopMergingScalarFieldsForToManyUninitializedAssociations(): void
    {
        $id = 2;

        $child = new DDC3699Child();

        $child->id          = $id;
        $child->childField  = 'childValue';
        $child->parentField = 'parentValue';

        $relation = new DDC3699RelationMany();

        $relation->id       = $id;
        $relation->child    = $child;
        $child->relations[] = $relation;

        $this->_em->persist($relation);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $unmanagedChild = $this->_em->find(DDC3699Child::class, $id);
        assert($unmanagedChild instanceof DDC3699Child);
        $this->_em->detach($unmanagedChild);

        // make it managed again
        $this->_em->find(DDC3699Child::class, $id);

        $unmanagedChild->childField  = 'modifiedChildValue';
        $unmanagedChild->parentField = 'modifiedParentValue';

        $mergedChild = $this->_em->merge($unmanagedChild);
        assert($mergedChild instanceof DDC3699Child);

        self::assertSame($mergedChild->childField, 'modifiedChildValue');
        self::assertSame($mergedChild->parentField, 'modifiedParentValue');
    }
}
