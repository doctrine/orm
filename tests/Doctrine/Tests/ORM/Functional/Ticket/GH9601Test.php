<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Tests\OrmFunctionalTestCase;

use function stream_get_contents;

/**
 * @group GH-9601
 */
class GH9601Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // ensure entity table exists
        $this->setUpEntitySchema([GH9601Entity::class]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $connection = static::$sharedConn;
        if ($connection === null) {
            return;
        }

        // remove persisted entities
        $connection->executeStatement('DELETE FROM GH9601Entity');
    }

    public function testIssue(): void
    {
        $counter1 = new GH9601Entity();
        $counter2 = new GH9601Entity();
        $this->_em->persist($counter1);
        $this->_em->persist($counter2);
        // before the fix is applied :
        // 'OCILob::writeTemporary(): Passing null to parameter #1 ($data) of type string is deprecated'
        $counter1->extra = null;
        $counter2->extra = 'test';
        $this->_em->flush();
        $this->_em->clear();

        $persistedCounter1 = $this->_em->find(GH9601Entity::class, $counter1->id);
        $persistedCounter2 = $this->_em->find(GH9601Entity::class, $counter2->id);

        // Assert entities were persisted
        self::assertInstanceOf(GH9601Entity::class, $persistedCounter1);
        self::assertInstanceOf(GH9601Entity::class, $persistedCounter2);
        self::assertNull($persistedCounter1->extra);
        self::assertIsResource($persistedCounter2->extra);

        $persistedCounter1->extra = 'test1';
        $this->_em->flush();
        $this->_em->clear();

        $persistedCounter1 = $this->_em->find(GH9601Entity::class, $counter1->id);
        self::assertIsResource($persistedCounter1->extra);
        self::assertEquals('test1', stream_get_contents($persistedCounter1->extra));

        $this->_em->clear();
        // Test update null value via DQL
        $dql = 'UPDATE Doctrine\Tests\ORM\Functional\Ticket\GH9601Entity a set a.extra = :val where a.id = :id';
        $this->_em->createQuery($dql)
           ->setParameters(
               new ArrayCollection([
                   new Parameter('id', $counter1->id),
                   new Parameter('val', null),
               ])
           )
           ->execute();
        $persistedCounter1 = $this->_em->find(GH9601Entity::class, $counter1->id);
        self::assertNull($persistedCounter1->extra);
    }
}


/**
 * @Entity
 */
class GH9601Entity
{
    /**
     * @var int
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var ?string
     * @Column(name="name", type="string", nullable=true)
     */
    public $name;

    /**
     * @var ?int
     * @Column(name="counter", type="integer", nullable=true)
     */
    public $counter;

    /**
     * @var ?bool
     * @Column(name="enabled", type="boolean", nullable=true)
     */
    public $enabled;

    /**
     * @var array
     * @Column(name="extra_array", type="json", scale=1, precision=2, nullable=true)
     */
    public $extraArray;

    /**
     * @var mixed
     * @Column(name="extra", type="blob", nullable=true)
     */
    public $extra;
    /**
     * @var mixed
     * @Column(name="extra_text", type="text", nullable=true)
     */
    public $extraText;
    /**
     * @var mixed
     * @Column(name="extra_object", type="object", nullable=true)
     */
    public $extraObject;
}
