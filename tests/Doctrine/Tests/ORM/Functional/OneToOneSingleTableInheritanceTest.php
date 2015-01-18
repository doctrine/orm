<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a unidirectional one-to-one association mapping (without inheritance).
 * Inverse side is not present.
 */
class OneToOneSingleTableInheritanceTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        //$this->useModelSet('ecommerce');

        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(Pet::CLASSNAME),
            $this->_em->getClassMetadata(Cat::CLASSNAME),
            $this->_em->getClassMetadata(LitterBox::CLASSNAME),
        ]);
    }

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

/** @Entity @InheritanceType("SINGLE_TABLE") @DiscriminatorMap({"cat" = "Cat"}) */
abstract class Pet
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}

/** @Entity */
class Cat extends Pet
{
    const CLASSNAME = __CLASS__;

    /**
     * @OneToOne(targetEntity="LitterBox")
     *
     * @var LitterBox
     */
    public $litterBox;
}

/** @Entity */
class LitterBox
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}
