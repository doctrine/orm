<?php
namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6217Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6217LazyEntity::class),
                $this->_em->getClassMetadata(GH6217EagerEntity::class),
                $this->_em->getClassMetadata(GH6217FetchedEntity::class),
            ]
        );
    }

    /**
     * @group 6217
     */
    public function testRetrievingCacheShouldNotThrowUndefinedIndexException()
    {
        $user = new GH6217LazyEntity();
        $category = new GH6217EagerEntity();
        $userProfile = new GH6217FetchedEntity($user, $category);

        $this->_em->persist($category);
        $this->_em->persist($user);
        $this->_em->persist($userProfile);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(GH6217FetchedEntity::class);
        $filters    = ['category' => $category->id];

        $this->assertCount(1, $repository->findBy($filters));
        $queryCount = $this->getCurrentQueryCount();

        $this->_em->clear();

        /* @var $found GH6217FetchedEntity[] */
        $found = $repository->findBy($filters);

        $this->assertCount(1, $found);
        $this->assertInstanceOf(GH6217FetchedEntity::class, $found[0]);
        $this->assertSame($user->id, $found[0]->user->id);
        $this->assertSame($category->id, $found[0]->category->id);
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}

/** @Entity @Cache(usage="NONSTRICT_READ_WRITE") */
class GH6217LazyEntity
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @Entity @Cache(usage="NONSTRICT_READ_WRITE") */
class GH6217EagerEntity
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @Entity @Cache(usage="NONSTRICT_READ_WRITE") */
class GH6217FetchedEntity
{
    /** @Id @Cache("NONSTRICT_READ_WRITE") @ManyToOne(targetEntity=GH6217LazyEntity::class) */
    public $user;

    /** @Id @Cache("NONSTRICT_READ_WRITE") @ManyToOne(targetEntity=GH6217EagerEntity::class, fetch="EAGER") */
    public $category;

    public function __construct(GH6217LazyEntity $user, GH6217EagerEntity $category)
    {
        $this->user = $user;
        $this->category = $category;
    }
}
