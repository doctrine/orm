<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
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
            $this->_em->getClassMetadata(Pet::CLASSNAME),
            $this->_em->getClassMetadata(Cat::CLASSNAME),
            $this->_em->getClassMetadata(LitterBox::CLASSNAME),
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
        $foundCat = $this->_em->find(Pet::CLASSNAME, $cat->id);

        $this->assertInstanceOf(Cat::CLASSNAME, $foundCat);
        $this->assertSame($cat->id, $foundCat->id);
        $this->assertInstanceOf(LitterBox::CLASSNAME, $foundCat->litterBox);
        $this->assertSame($cat->litterBox->id, $foundCat->litterBox->id);
    }
}
