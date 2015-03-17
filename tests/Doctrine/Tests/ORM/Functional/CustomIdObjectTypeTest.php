<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeChild;
use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\DBAL\Types\Type as DBALType;

class CustomIdObjectTypeTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType('CustomIdObject')) {
            DBALType::overrideType('CustomIdObject', '\Doctrine\Tests\DbalTypes\CustomIdObjectType');
        } else {
            DBALType::addType('CustomIdObject', '\Doctrine\Tests\DbalTypes\CustomIdObjectType');
        }

        $this->useModelSet('custom_id_object_type');
        parent::setUp();
    }

    public function testFindByCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent('foo');

        $this->_em->persist($parent);
        $this->_em->flush();

        $result = $this->_em->find('Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent', $parent->id);

        $this->assertSame($parent, $result);
    }

    public function testFetchJoinCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent('foo');
        $parent->children->add(new CustomIdObjectTypeChild('bar', $parent));

        $this->_em->persist($parent);
        $this->_em->flush();

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('parent')
            ->from('Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent', 'parent')
            ->addSelect('children')
            ->leftJoin('parent.children', 'children')
        ;

        $result = $qb->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertSame($parent, $result[0]);
    }

    public function testFetchJoinWhereCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent('foo');
        $parent->children->add(new CustomIdObjectTypeChild('bar', $parent));

        $this->_em->persist($parent);
        $this->_em->flush();

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('parent')
            ->from('Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent', 'parent')
            ->addSelect('children')
            ->leftJoin('parent.children', 'children')
            ->where('children.id = ?1')
            ->setParameter(1, $parent->children->first()->id);
        ;

        $result = $qb->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertSame($parent, $result[0]);
    }
}
