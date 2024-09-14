<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Version;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH8663Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH8663VersionedEntity::class);
    }

    public function testDeletedEntity(): void
    {
        $result = $this->_em->find(GH8663VersionedEntity::class, 1, LockMode::OPTIMISTIC);

        self::assertNull($result);
    }
}

/** @Entity */
class GH8663VersionedEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Version
     * @Column(type="integer")
     * @var int
     */
    protected $version;
}
