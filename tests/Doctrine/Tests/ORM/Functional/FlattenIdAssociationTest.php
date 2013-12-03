<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\Models\DDC2645\DDC2645Price;
use Doctrine\Tests\Models\DDC2645\DDC2645Variant;

/**
 * DDC2645
 *
 * @author Exeu <exeu65@googlemail.com>
 */
class FlattenIdAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ddc2645');
        parent::setUp();
    }

    public function testFlattenIdAssociationMerge()
    {
        $variant = new DDC2645Variant();
        $variant->setId('abc');
        $variant->setName('foo');

        $this->_em->persist($variant);
        $this->_em->flush();

        $price = new DDC2645Price();
        $price->setCountry('de');
        $price->setValue(12.2);
        $price->setType(1);
        $price->setVariant($variant);

        $success = true;
        try {
            $price = $this->_em->merge($price);
        } catch (ORMException $e) {
            $success = false;
        }

        $this->assertTrue($success);
    }
}