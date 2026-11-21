<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Version;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

final class GH7067Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->setUpEntitySchema([GH7067Entity::class]);
    }

    #[Group('GH-7067')]
    public function testSLCWithVersion(): void
    {
        $entity             = new GH7067Entity();
        $entity->lastUpdate = new DateTimeImmutable();

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $notCached = $this->_em->find(GH7067Entity::class, $entity->id);
        assert($notCached instanceof GH7067Entity);

        self::assertNotNull($notCached->version, 'Version already cached by persister above, it must be not null');

        $notCached->lastUpdate = new DateTimeImmutable('+1 seconds');

        $this->_em->flush();
        $this->_em->clear();
    }
}

#[Entity]
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH7067Entity
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var DateTimeImmutable */
    #[Column(type: 'datetime_immutable')]
    public $lastUpdate;

    /** @var DateTimeImmutable */
    #[Column(type: 'datetime_immutable')]
    #[Version]
    public $version;
}
