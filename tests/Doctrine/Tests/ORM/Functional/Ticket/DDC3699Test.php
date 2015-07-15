<?php

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Tests\Models\DDC3699\DDC3699Parent;
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

        $this->_em->persist($relation);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        // fixtures loaded
        /* @var $unManagedChild DDC3699Child */
        $unManagedChild = $this->_em->find(DDC3699Child::CLASSNAME, $id);

        $this->_em->detach($unManagedChild);

        // make it managed again
        $this->_em->find(DDC3699Child::CLASSNAME, $id);

        $unManagedChild->childField  = 'modifiedChildValue';
        $unManagedChild->parentField = 'modifiedParentValue';

        /* @var $mergedChild DDC3699Child */
        $mergedChild = $this->_em->merge($unManagedChild);

        $this->assertSame($mergedChild->childField, 'modifiedChildValue');
        $this->assertSame($mergedChild->parentField, 'modifiedParentValue');
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

        $this->_em->persist($relation);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        /* @var $unmanagedChild DDC3699Child */
        $unmanagedChild = $this->_em->find(DDC3699Child::CLASSNAME, $id);
        $this->_em->detach($unmanagedChild);

        // make it managed again
        $this->_em->find(DDC3699Child::CLASSNAME, $id);

        $unmanagedChild->childField  = 'modifiedChildValue';
        $unmanagedChild->parentField = 'modifiedParentValue';

        /* @var $mergedChild DDC3699Child */
        $mergedChild = $this->_em->merge($unmanagedChild);

        $this->assertSame($mergedChild->childField, 'modifiedChildValue');
        $this->assertSame($mergedChild->parentField, 'modifiedParentValue');
    }
}