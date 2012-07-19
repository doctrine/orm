<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\Models\DDC1925\DDC1925User;
use Doctrine\Tests\Models\DDC1925\DDC1925Product;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1925
 */
class DDC1925Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    /**
     * @var \Doctrine\Tests\Models\Quote\User
     */
    private $email;

    protected function setUp()
    {
        parent::setUp();

        //try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\Models\DDC1925\DDC1925User'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\DDC1925\DDC1925Product'),
            ));
        //} catch(\Exception $e) {
        //}

    }

    public function testIssue()
    {
        $user = new DDC1925User();
        $user->setTitle("Test User");
        $this->_em->persist($user);

        $product = new DDC1925Product();
        $product->setTitle("Test product");
        $this->_em->persist($product);
        $this->_em->flush();

        $product->addBuyer($user);

        $this->_em->getUnitOfWork()->computeChangeSets();

        $this->_em->persist($product);
        $this->_em->flush();
    }
}