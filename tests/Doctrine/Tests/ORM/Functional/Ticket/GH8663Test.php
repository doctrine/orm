<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH8663Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(GH8663VersionedEntity::class),
        ]);
    }

    protected function tearDown(): void
    {
        $this->_schemaTool->dropSchema([
            $this->_em->getClassMetadata(GH8663VersionedEntity::class),
        ]);

        parent::tearDown();
    }

    public function testDeletedEntity(): void
    {
        $result = $this->_em->find(GH8663VersionedEntity::class, 1, LockMode::OPTIMISTIC);

        $this->assertNull($result);
    }
}

/**
 * @Entity
 */
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
