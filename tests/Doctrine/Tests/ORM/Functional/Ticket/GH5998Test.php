<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\DBAL\LockMode;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-5998
 */
class GH5998Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(GH5998Main::class),
            $this->em->getClassMetadata(GH5998Child::class),
            ]
        );
    }

    /**
     * Verifies that MappedSuperclasses work within a JTI hierarchy.
     */
    public function testIssue()
    {
        // Test insert
        $child = new GH5998Child('Sam', 0, 1);
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        // Test find
        $child = $this->em->getRepository(GH5998Child::class)->find(1);
        self::assertNotNull($child);

        // Test lock and update
        $this->em->transactional(function($em) use ($child) {
            $em->lock($child, LockMode::NONE);
            $child->firstName = 'Bob';
            $child->status = 0;
        });
        $this->em->clear();
        $child = $this->em->getRepository(GH5998Child::class)->find(1);
        self::assertEquals($child->firstName, 'Bob');
        self::assertEquals($child->status, 0);

        // Test delete
        $this->em->remove($child);
        $this->em->flush();
        $child = $this->em->getRepository(GH5998Child::class)->find(1);
        self::assertNull($child);
    }
}

/**
 * @ORM\MappedSuperclass
 */
class GH5998Common
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /**
     * @ORM\Column(type="integer");
     */
    public $status;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({"child" = "Doctrine\Tests\ORM\Functional\Ticket\GH5998Child"})
 */
abstract class GH5998Main extends GH5998Common
{
    /**
     * @ORM\Column(type="string", length=255);
     */
    public $firstName;
}

/**
 * @ORM\Entity
 */
class GH5998Child extends GH5998Main
{
    /**
     * @ORM\Column(type="integer")
     */
    public $type;
    function __construct(string $firstName, int $type, int $status)
    {
        $this->firstName = $firstName;
        $this->type = $type;
        $this->status = $status;
    }
}
