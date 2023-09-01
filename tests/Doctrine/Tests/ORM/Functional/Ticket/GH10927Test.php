<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10927Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10927Parent::class,
            GH10927Child::class,
        ]);
    }

    public function testChildEntitySequenceShouldBeCorrect(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->usesSequenceEmulatedIdentityColumns()) {
            self::markTestSkipped(
                'This test is special to platforms emulating IDENTITY key generation strategy through sequences.'
            );
        }

        $child = new GH10927Child();

        $this->_em->persist($child);
        $this->_em->flush();
        self::assertNotNull($child->getId());
    }
}

/**
 * @ORM\MappedSuperclass()
 */
class GH10927Parent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="gh10927_child")
 */
class GH10927Child extends GH10927Parent
{
}
