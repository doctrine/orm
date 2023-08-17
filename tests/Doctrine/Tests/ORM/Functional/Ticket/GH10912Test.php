<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function array_values;
use function strpos;

class GH10912Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10912User::class,
            GH10912Profile::class,
            GH10912Room::class,
        ]);
    }

    public function testIssue(): void
    {
        $user    = new GH10912User();
        $profile = new GH10912Profile();
        $room    = new GH10912Room();

        $user->rooms->add($room);
        $user->profile = $profile;
        $profile->user = $user;
        $room->user    = $user;

        $this->_em->persist($room);
        $this->_em->persist($user);
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $userReloaded = $this->_em->find(GH10912User::class, $user->id);

        $queryLog = $this->getQueryLog();
        $queryLog->reset()->enable();

        $this->_em->remove($userReloaded);
        $this->_em->flush();

        $queries = array_values(array_filter($queryLog->queries, static function ($entry) {
            return strpos($entry['sql'], 'DELETE') === 0;
        }));

        self::assertCount(3, $queries);
        self::assertSame('DELETE FROM GH10912Room WHERE id = ?', $queries[0]['sql']);
        self::assertSame('DELETE FROM GH10912Profile WHERE id = ?', $queries[1]['sql']);
        self::assertSame('DELETE FROM GH10912User WHERE id = ?', $queries[2]['sql']);

        // The EntityManager is aware that all three entities have been deleted
        $im = $this->_em->getUnitOfWork()->getIdentityMap();
        self::assertEmpty($im[GH10912Profile::class]);
        self::assertEmpty($im[GH10912User::class]);
        self::assertEmpty($im[GH10912Room::class]);
    }
}

/** @ORM\Entity */
class GH10912User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity=GH10912Room::class, mappedBy="user", cascade={"remove"})
     *
     * @var Collection<int, GH10912Room>
     */
    public $rooms;

    /**
     * @ORM\OneToOne(targetEntity=GH10912Profile::class, cascade={"remove"})
     * @ORM\JoinColumn(onDelete="cascade")
     *
     * @var GH10912Profile
     */
    public $profile;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
    }
}

/** @ORM\Entity */
class GH10912Profile
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity=GH10912User::class)
     * @ORM\JoinColumn(onDelete="cascade")
     *
     * @var GH10912User
     */
    public $user;
}

/** @ORM\Entity */
class GH10912Room
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH10912User::class, inversedBy="rooms")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var GH10912User
     */
    public $user;
}
