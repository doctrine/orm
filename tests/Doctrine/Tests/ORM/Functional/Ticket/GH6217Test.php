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
                $this->_em->getClassMetadata(GH6217User::class),
                $this->_em->getClassMetadata(GH6217Profile::class),
                $this->_em->getClassMetadata(GH6217Category::class),
                $this->_em->getClassMetadata(GH6217UserProfile::class),
            ]
        );
    }

    /**
     * @group 6217
     */
    public function testRetrievingCacheShouldNotThrowUndefinedIndexException()
    {
        $user = new GH6217User(1);
        $profile = new GH6217Profile();
        $category = new GH6217Category(1);
        $userProfile = new GH6217UserProfile($user, $profile, $category);

        $this->_em->persist($category);
        $this->_em->persist($user);
        $this->_em->persist($profile);
        $this->_em->persist($userProfile);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(GH6217UserProfile::class);
        $filters = ['user' => 1, 'category' => 1];

        $this->assertCount(1, $repository->findBy($filters));
        $queryCount = $this->getCurrentQueryCount();

        $this->_em->clear();

        $this->assertCount(1, $repository->findBy($filters));
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH6217User
{
    /**
     * @Id
     * @Column(type="integer")
     *
     * @var int
     */
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

/** @Entity @Cache(usage="NONSTRICT_READ_WRITE") */
class GH6217Profile
{
    /**
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     *
     * @var string
     */
    public $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH6217Category
{
    /**
     * @Id
     * @Column(type="integer")
     *
     * @var int
     */
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class GH6217UserProfile
{
    /**
     * @Id
     * @Cache("NONSTRICT_READ_WRITE")
     * @ManyToOne(targetEntity="GH6217User")
     * @JoinColumn(nullable=false)
     *
     * @var GH6217User
     */
    public $user;

    /**
     * @Id
     * @Cache("NONSTRICT_READ_WRITE")
     * @ManyToOne(targetEntity="GH6217Profile", fetch="EAGER")
     *
     * @var GH6217Profile
     */
    public $profile;

    /**
     * @Id
     * @Cache("NONSTRICT_READ_WRITE")
     * @ManyToOne(targetEntity="GH6217Category", fetch="EAGER")
     *
     * @var GH6217Category
     */
    public $category;

    public function __construct(GH6217User $user, GH6217Profile $profile, GH6217Category $category)
    {
        $this->user = $user;
        $this->profile = $profile;
        $this->category = $category;
    }
}
