<?php

use Doctrine\Tests\Models\DDC3699\DDC3699RelationOne;
use Doctrine\Tests\Models\DDC3699\DDC3699RelationMany;
use Doctrine\Tests\Models\DDC3699\DDC3699Child;

/**
 * @group DDC-3699
 */
class DDC3597Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ddc3699');

        parent::setUp();
    }

    /**
     * @group DDC-3699
     */
    public function testMergingParentClassFieldsDoesNotStopMergingScalarFieldsForToOneUninitializedAssociations()
    {
        $id = 1;

        $child = new DDC3699Child();

        $child->id          = $id;
        $child->childField  = 'childValue';
        $child->parentField = 'parentValue';

        $relation = new DDC3699RelationOne();

        $relation->id       = $id;
        $relation->child    = $child ;
        $child->oneRelation = $relation;

        $this->em->persist($relation);
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        // fixtures loaded
        /* @var $unManagedChild DDC3699Child */
        $unManagedChild = $this->em->find(DDC3699Child::class, $id);

        $this->em->detach($unManagedChild);

        // make it managed again
        $this->em->find(DDC3699Child::class, $id);

        $unManagedChild->childField  = 'modifiedChildValue';
        $unManagedChild->parentField = 'modifiedParentValue';

        /* @var $mergedChild DDC3699Child */
        $mergedChild = $this->em->merge($unManagedChild);

        self::assertSame($mergedChild->childField, 'modifiedChildValue');
        self::assertSame($mergedChild->parentField, 'modifiedParentValue');
    }

    /**
     * @group DDC-3699
     */
    public function testMergingParentClassFieldsDoesNotStopMergingScalarFieldsForToManyUninitializedAssociations()
    {
        $id = 2;

        $child = new DDC3699Child();

        $child->id          = $id;
        $child->childField  = 'childValue';
        $child->parentField = 'parentValue';

        $relation = new DDC3699RelationMany();

        $relation->id       = $id;
        $relation->child    = $child ;
        $child->relations[] = $relation;

        $this->em->persist($relation);
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        /* @var $unmanagedChild DDC3699Child */
        $unmanagedChild = $this->em->find(DDC3699Child::class, $id);
        $this->em->detach($unmanagedChild);

        // make it managed again
        $this->em->find(DDC3699Child::class, $id);

        $unmanagedChild->childField  = 'modifiedChildValue';
        $unmanagedChild->parentField = 'modifiedParentValue';

        /* @var $mergedChild DDC3699Child */
        $mergedChild = $this->em->merge($unmanagedChild);

        self::assertSame($mergedChild->childField, 'modifiedChildValue');
        self::assertSame($mergedChild->parentField, 'modifiedParentValue');
    }
}
