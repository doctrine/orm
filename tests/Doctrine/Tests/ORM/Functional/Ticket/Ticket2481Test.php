<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

class Ticket2481Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [$this->em->getClassMetadata(Ticket2481Product::class)]
            );
        } catch (Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
        $this->conn = $this->em->getConnection();
    }

    public function testEmptyInsert() : void
    {
        $test = new Ticket2481Product();
        $this->em->persist($test);
        $this->em->flush();

        self::assertGreaterThan(0, $test->id);
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
