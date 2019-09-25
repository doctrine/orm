<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-5998
 */
class GH5998Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(GH5998JTI::class),
            $this->em->getClassMetadata(GH5998JTIChild::class),
            $this->em->getClassMetadata(GH5998STI::class),
            $this->em->getClassMetadata(GH5998Related::class),
        ]);
    }

    /**
     * Verifies that MappedSuperclasses work within an inheritance hierarchy.
     */
    public function testIssue()
    {
        // Test JTI
        $this->classTests(GH5998JTIChild::class);
        // Test STI
        $this->classTests(GH5998STIChild::class);
    }

    private function classTests($className)
    {
        // Test insert
        $child = new $className('Sam', 0, 1);
        $child->rel = new GH5998Related();
        $this->em->persist($child);
        $this->em->persist($child->rel);
        $this->em->flush();
        $this->em->clear();

        // Test find
        $child = $this->em->getRepository($className)->find(1);
        self::assertNotNull($child);

        // Test lock and update
        $this->em->transactional(static function ($em) use ($child) {
            $em->lock($child, LockMode::NONE);
            $child->firstName = 'Bob';
            $child->status    = 0;
        });
        $this->em->clear();
        $child = $this->em->getRepository($className)->find(1);
        self::assertEquals($child->firstName, 'Bob');
        self::assertEquals($child->status, 0);

        // Test delete
        $this->em->remove($child);
        $this->em->flush();
        $child = $this->em->getRepository($className)->find(1);
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
     * @ORM\ManyToOne(targetEntity=GH5998Related::class)
     * @ORM\JoinColumn(name="related_id", referencedColumnName="id")
     */
    public $rel;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({"child" = GH5998JTIChild::class})
 */
abstract class GH5998JTI extends GH5998Common
{
    /** @ORM\Column(type="string", length=255) */
    public $firstName;
}

/**
 * @ORM\MappedSuperclass
 */
class GH5998JTICommon extends GH5998JTI
{
    /** @ORM\Column(type="integer") */
    public $status;
}

/**
 * @ORM\Entity
 */
class GH5998JTIChild extends GH5998JTICommon
{
    /** @ORM\Column(type="integer") */
    public $type;

    public function __construct(string $firstName, int $type, int $status)
    {
        $this->firstName = $firstName;
        $this->type      = $type;
        $this->status    = $status;
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"child" = GH5998STIChild::class})
 */
abstract class GH5998STI extends GH5998Common
{
    /** @ORM\Column(type="string", length=255) */
    public $firstName;
}

/**
 * @ORM\MappedSuperclass
 */
class GH5998STICommon extends GH5998STI
{
    /** @ORM\Column(type="integer") */
    public $status;
}

/**
 * @ORM\Entity
 */
class GH5998STIChild extends GH5998STICommon
{
    /** @ORM\Column(type="integer") */
    public $type;

    public function __construct(string $firstName, int $type, int $status)
    {
        $this->firstName = $firstName;
        $this->type      = $type;
        $this->status    = $status;
    }
}

/**
 * @ORM\Entity
 */
class GH5998Related
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}
