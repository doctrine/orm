<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\OneToOneSingleTableInheritance\Cat;
use Doctrine\Tests\Models\OneToOneSingleTableInheritance\LitterBox;
use Doctrine\Tests\Models\OneToOneSingleTableInheritance\Pet;
use Doctrine\Tests\OrmFunctionalTestCase;

class OneToOneSingleTableInheritanceTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(Pet::class),
            $this->em->getClassMetadata(Cat::class),
            $this->em->getClassMetadata(LitterBox::class),
        ]);
    }

    /**
     * Tests a unidirectional one-to-one association mapping from an inheritance child class
     *
     * @group DDC-3517
     * @group #1265
     */
    public function testFindFromOneToOneOwningSideJoinedTableInheritance()
    {
        $cat            = new Cat();
        $cat->litterBox = new LitterBox();

        $this->em->persist($cat);
        $this->em->persist($cat->litterBox);
        $this->em->flush();
        $this->em->clear();

        /* @var $foundCat Cat */
        $foundCat = $this->em->find(Pet::class, $cat->id);

        self::assertInstanceOf(Cat::class, $foundCat);
        self::assertSame($cat->id, $foundCat->id);
        self::assertInstanceOf(LitterBox::class, $foundCat->litterBox);
        self::assertSame($cat->litterBox->id, $foundCat->litterBox->id);
    }
}
