<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity;
use Doctrine\Tests\Models\DDC5876\DCC5876ChildRelationEntity;
use Doctrine\Tests\Models\DDC5876\DCC5876RootEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC5876Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(array(
            DCC5876RootEntity::CLASSNAME,
            DCC5876ChildEntity::CLASSNAME,
            DCC5876ChildRelationEntity::CLASSNAME,
        ));


        $this->loadFixture();
    }

    public function testQueryRootEntity()
    {
        $this->_em->getRepository(DCC5876RootEntity::CLASSNAME)->createQueryBuilder('root')
            ->leftJoin('root.childEntities', 'childEntities')
            ->getQuery()->getResult()
        ;
    }

    public function testQueryRootEntityAndChild()
    {
        $this->_em->getRepository(DCC5876RootEntity::CLASSNAME)->createQueryBuilder('root')
            ->addSelect('childEntities') // <-- this will break query
            ->leftJoin('root.childEntities', 'childEntities')
            ->getQuery()->getResult()
        ;
    }

    private function loadFixture()
    {
        $root = new DCC5876RootEntity();
        $this->_em->persist($root);
        $child = new DCC5876ChildEntity();
        $this->_em->persist($child);
        $childRelation = new DCC5876ChildRelationEntity();
        $this->_em->persist($childRelation);

        $root->addChildEntity($child);
        $child->addChildRelationEntity($childRelation);

        $this->_em->flush();
        $this->_em->clear();
    }
}
