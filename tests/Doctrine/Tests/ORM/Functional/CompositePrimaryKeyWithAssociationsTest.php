<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\OrmFunctionalTestCase;

class CompositePrimaryKeyWithAssociationsTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('geonames');
        parent::setUp();

        $it = new Country("IT", "Italy");

        $this->em->persist($it);
        $this->em->flush();

        $admin1 = new Admin1(1, "Rome", $it);

        $this->em->persist($admin1);
        $this->em->flush();

        $name1 = new Admin1AlternateName(1, "Roma", $admin1);
        $name2 = new Admin1AlternateName(2, "Rome", $admin1);

        $admin1->names[] = $name1;
        $admin1->names[] = $name2;

        $this->em->persist($admin1);
        $this->em->persist($name1);
        $this->em->persist($name2);

        $this->em->flush();

        $this->em->clear();
    }

    public function testFindByAbleToGetCompositeEntitiesWithMixedTypeIdentifiers()
    {
        $admin1Repo      = $this->em->getRepository(Admin1::class);
        $admin1NamesRepo = $this->em->getRepository(Admin1AlternateName::class);

        $admin1Rome = $admin1Repo->findOneBy(['country' => 'IT', 'id' => 1]);

        $names = $admin1NamesRepo->findBy(['admin1' => $admin1Rome]);
        self::assertCount(2, $names);

        $name1 = $admin1NamesRepo->findOneBy(['admin1' => $admin1Rome, 'id' => 1]);
        $name2 = $admin1NamesRepo->findOneBy(['admin1' => $admin1Rome, 'id' => 2]);

        self::assertEquals(1, $name1->id);
        self::assertEquals("Roma", $name1->name);

        self::assertEquals(2, $name2->id);
        self::assertEquals("Rome", $name2->name);
    }
}
