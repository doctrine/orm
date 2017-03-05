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

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(Pet::class),
            $this->_em->getClassMetadata(Cat::class),
            $this->_em->getClassMetadata(LitterBox::class),
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

        $this->_em->persist($cat);
        $this->_em->persist($cat->litterBox);
        $this->_em->flush();
        $this->_em->clear();

        /* @var $foundCat Cat */
        $foundCat = $this->_em->find(Pet::class, $cat->id);

        $this->assertInstanceOf(Cat::class, $foundCat);
        $this->assertSame($cat->id, $foundCat->id);
        $this->assertInstanceOf(LitterBox::class, $foundCat->litterBox);
        $this->assertSame($cat->litterBox->id, $foundCat->litterBox->id);
    }
}
