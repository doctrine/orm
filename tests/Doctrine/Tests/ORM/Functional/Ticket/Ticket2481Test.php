<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

class Ticket2481Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(Ticket2481Product::class)
                ]
            );
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
        $this->conn = $this->em->getConnection();
    }

    public function testEmptyInsert()
    {
        $test = new Ticket2481Product();
        $this->em->persist($test);
        $this->em->flush();

        self::assertTrue($test->id > 0);
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ticket_2481_products")
 */
class Ticket2481Product
{
  /**
   * @ORM\Id @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  public $id;
}
