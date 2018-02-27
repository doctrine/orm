<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

final class GH7067Test extends \Doctrine\Tests\OrmFunctionalTestCase
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
        $entity->lastUpdate = new \DateTime();

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH7067Entity $notCached */
        $notCached = $this->_em->find(GH7067Entity::class, $entity->id);

        self::assertNotNull($notCached->version, 'Version already cached by persister above, it must be not null');

        $notCached->lastUpdate = new \DateTime('+1 seconds');

        $this->_em->flush();
        $this->_em->clear();
    }
}

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH7067Entity
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @Column(type="datetime")
     *
     * @var \DateTime
     */
    public $lastUpdate;

    /**
     * @Column(type="datetime")
     * @Version
     *
     * @var \DateTime
     */
    public $version;
}
