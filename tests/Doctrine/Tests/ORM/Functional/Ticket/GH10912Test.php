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

        /*
         * This issue is about finding a special deletion order:
         * $user and $profile cross-reference each other with ON DELETE CASCADE.
         * So, whichever one gets deleted first, the DBMS will immediately dispose
         * of the other one as well.
         *
         * $user -> $room is the unproblematic (irrelevant) inverse side of
         * a OneToMany association.
         *
         * $room -> $user is a not-nullable, no DBMS-level-cascade, owning side
         * of ManyToOne. We *must* remove the $room _before_ the $user can be
         * deleted. And remember, $user deletion happens either when we DELETE the
         * user (direct deletion), or when we delete the $profile (ON DELETE CASCADE
         * propagates to the user).
         *
         * In the original bug report, the ordering of fields in the entities was
         * relevant, in combination with a cascade=persist configuration.
         *
         * But, for the sake of clarity, let's put these features away and create
         * the problematic sequence in UnitOfWork::$entityDeletions directly:
         */
        $this->_em->remove($profile);
        $this->_em->remove($user);
        $this->_em->remove($room);

        $queryLog = $this->getQueryLog();
        $queryLog->reset()->enable();

        $this->_em->flush();

        $queries = array_values(array_filter($queryLog->queries, static function (array $entry): bool {
            return strpos($entry['sql'], 'DELETE') === 0;
        }));

        self::assertCount(3, $queries);

        // we do not care about the order of $user vs. $profile, so do not check them.
        self::assertSame('DELETE FROM GH10912Room WHERE id = ?', $queries[0]['sql'], '$room deletion is the first query');

        // The EntityManager is aware that all three entities have been deleted (sanity check)
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
     * @ORM\OneToMany(targetEntity=GH10912Room::class, mappedBy="user")
     *
     * @var Collection<int, GH10912Room>
     */
    public $rooms;

    /**
     * @ORM\OneToOne(targetEntity=GH10912Profile::class)
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
