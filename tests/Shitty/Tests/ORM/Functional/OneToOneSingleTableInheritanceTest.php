<?php

namespace Shitty\Tests\ORM\Functional;

use Shitty\Tests\Models\ECommerce\ECommerceProduct;
use Shitty\Tests\Models\ECommerce\ECommerceShipping;
use Shitty\ORM\Mapping\AssociationMapping;
use Shitty\ORM\Mapping\ClassMetadata;
use Shitty\ORM\Query;
use Shitty\Tests\Models\OneToOneSingleTableInheritance\Cat;
use Shitty\Tests\Models\OneToOneSingleTableInheritance\LitterBox;
use Shitty\Tests\Models\OneToOneSingleTableInheritance\Pet;
use Shitty\Tests\OrmFunctionalTestCase;

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
