<?php

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
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(DDC3699Parent::CLASSNAME),
                $this->_em->getClassMetadata(DDC3699RelationOne::CLASSNAME),
                $this->_em->getClassMetadata(DDC3699RelationMany::CLASSNAME),
                $this->_em->getClassMetadata(DDC3699Child::CLASSNAME),
            ));
        } catch (\Exception $e) {
            // should throw error on second because schema is already created
        }
    }

    private function createChild($id, $relationClass, $relationMethod)
    {
        // element in DB
        $child = new DDC3699Child();
        $child->setId($id);
        $child->setChildField('childValue');
        $child->setParentField('parentValue');

        $relation = new $relationClass();
        $relation->setId($id);
        $relation->setChild($child);
        $child->$relationMethod($relation);

        $this->_em->persist($relation);
        $this->_em->persist($child);
        $this->_em->flush();

        // detach
        $this->_em->detach($relation);
        $this->_em->detach($child);
    }

    /**
     * @group DDC-3699
     */
    public function testMergeParentEntityFieldsOne()
    {
        $id = 1;
        $this->createChild($id, DDC3699RelationOne::CLASSNAME, 'setOneRelation');

        $unmanagedChild = $this->_em->find(DDC3699Child::CLASSNAME, $id);
        $this->_em->detach($unmanagedChild);

        // make it managed again
        $this->_em->find(DDC3699Child::CLASSNAME, $id);

        $unmanagedChild->setChildField('modifiedChildValue');
        $unmanagedChild->setParentField('modifiedParentValue');

        $mergedChild = $this->_em->merge($unmanagedChild);

        $this->assertEquals($mergedChild->getChildField(), 'modifiedChildValue');
        $this->assertEquals($mergedChild->getParentField(), 'modifiedParentValue');
    }

    /**
     * @group DDC-3699
     */
    public function testMergeParentEntityFieldsMany()
    {
        $id = 2;
        $this->createChild($id, DDC3699RelationMany::CLASSNAME, 'addRelation');

        $unmanagedChild = $this->_em->find(DDC3699Child::CLASSNAME, $id);
        $this->_em->detach($unmanagedChild);

        // make it managed again
        $this->_em->find(DDC3699Child::CLASSNAME, $id);

        $unmanagedChild->setChildField('modifiedChildValue');
        $unmanagedChild->setParentField('modifiedParentValue');

        $mergedChild = $this->_em->merge($unmanagedChild);

        $this->assertEquals($mergedChild->getChildField(), 'modifiedChildValue');
        $this->assertEquals($mergedChild->getParentField(), 'modifiedParentValue');
    }
}