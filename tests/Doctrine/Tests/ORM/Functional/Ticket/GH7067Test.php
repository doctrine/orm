<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7067Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->setUpEntitySchema([GH7067Entity::class]);
    }

    /**
     * @group 7067
     */
    public function testSLCWithVersion() : void
    {
        $entity             = new GH7067Entity();
        $entity->lastUpdate = new DateTime();

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        /** @var GH7067Entity $notCached */
        $notCached = $this->em->find(GH7067Entity::class, $entity->id);

        self::assertNotNull($notCached->version, 'Version already cached by persister above, it must be not null');

        $notCached->lastUpdate = new DateTime('+1 second');

        $this->em->flush();
        $this->em->clear();
    }
}

/**
 * @ORM\Entity()
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH7067Entity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var DateTime
     */
    public $lastUpdate;

    /**
     * @ORM\Column(type="datetime")
     * @ORM\Version()
     *
     * @var DateTime
     */
    public $version;
}
