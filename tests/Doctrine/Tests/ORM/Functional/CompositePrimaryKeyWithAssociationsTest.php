<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;

class CompositePrimaryKeyWithAssociationsTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('geonames');
        parent::setUp();

        $it = new Country("IT", "Italy");

        $this->_em->persist($it);
        $this->_em->flush();

        $admin1 = new Admin1(1, "Rome", $it);

        $this->_em->persist($admin1);
        $this->_em->flush();

        $name1 = new Admin1AlternateName(1, "Roma", $admin1);
        $name2 = new Admin1AlternateName(2, "Rome", $admin1);

        $admin1->names[] = $name1;
        $admin1->names[] = $name2;

        $this->_em->persist($admin1);
        $this->_em->persist($name1);
        $this->_em->persist($name2);

        $this->_em->flush();

        $this->_em->clear();
    }

    public function testFindByAbleToGetCompositeEntitiesWithMixedTypeIdentifiers()
    {
        $admin1Repo      = $this->_em->getRepository('Doctrine\Tests\Models\GeoNames\Admin1');
        $admin1NamesRepo = $this->_em->getRepository('Doctrine\Tests\Models\GeoNames\Admin1AlternateName');

        $admin1Rome = $admin1Repo->findOneBy(array('country' => 'IT', 'id' => 1));

        $names = $admin1NamesRepo->findBy(array('admin1' => $admin1Rome));
        $this->assertCount(2, $names);

        $name1 = $admin1NamesRepo->findOneBy(array('admin1' => $admin1Rome, 'id' => 1));
        $name2 = $admin1NamesRepo->findOneBy(array('admin1' => $admin1Rome, 'id' => 2));

        $this->assertEquals(1, $name1->id);
        $this->assertEquals("Roma", $name1->name);

        $this->assertEquals(2, $name2->id);
        $this->assertEquals("Rome", $name2->name);
    }
}
