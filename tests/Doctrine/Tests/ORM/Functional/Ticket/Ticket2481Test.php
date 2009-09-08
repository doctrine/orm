<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class Ticket2481Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\Ticket2481Product')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
        $this->_conn = $this->_em->getConnection();
    }

    public function testEmptyInsert()
    {
        $test = new Ticket2481Product();
        $this->_em->persist($test);
        $this->_em->flush();

        $this->assertTrue($test->id > 0);
    }
}

/**
 * @Entity
 * @Table(name="ticket_2481_products")
 */
class Ticket2481Product
{
  /**
   * @Id @Column(type="integer")
   * @GeneratedValue(strategy="AUTO")
   */
  public $id;
}