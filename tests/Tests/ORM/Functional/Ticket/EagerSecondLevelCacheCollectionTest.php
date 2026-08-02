<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Reproduces duplicate hydration of EAGER OneToMany collections when the owner is loaded from the
 * second-level cache, the collection is initialized (e.g. via the collection cache), and a later
 * hydration triggers deferred eager collection loading.
 */
final class EagerSecondLevelCacheCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->createSchemaForModels(
            EagerSlcUser::class,
            EagerSlcRole::class,
            EagerSlcOther::class,
        );
    }

    public function testEagerCollectionIsNotDuplicatedAfterSecondLevelCacheHit(): void
    {
        $user = new EagerSlcUser('alice');
        $user->addRole(new EagerSlcRole($user, 'ROLE_USER'));
        $user->addRole(new EagerSlcRole($user, 'ROLE_ADMIN'));
        $other = new EagerSlcOther('marker');

        $this->_em->persist($user);
        $this->_em->persist($other);
        $this->_em->flush();

        $userId  = $user->id;
        $otherId = $other->id;

        $this->_em->clear();

        // Warm entity + collection cache (first load misses, then puts).
        $warmed = $this->_em->find(EagerSlcUser::class, $userId);
        self::assertNotNull($warmed);
        self::assertCount(2, $warmed->roles);
        $this->_em->clear();

        // Load owner from the entity second-level cache. EAGER schedules deferred collection
        // loading, but DefaultEntityHydrator does not call triggerEagerLoads().
        $cachedUser = $this->_em->find(EagerSlcUser::class, $userId);
        self::assertNotNull($cachedUser);

        // Initialize the collection (collection-cache hit or DB) before deferred eager runs.
        self::assertCount(2, $cachedUser->roles);

        // A subsequent hydration flushes pending deferred eager collection loads.
        $this->_em->find(EagerSlcOther::class, $otherId);

        self::assertCount(
            2,
            $cachedUser->roles,
            'EAGER OneToMany must not be hydrated twice after a second-level cache hit',
        );
    }
}

#[Entity]
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class EagerSlcUser
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;

    /** @var Collection<int, EagerSlcRole> */
    #[Cache(usage: 'NONSTRICT_READ_WRITE')]
    #[OneToMany(targetEntity: EagerSlcRole::class, mappedBy: 'user', cascade: ['persist'], fetch: 'EAGER')]
    public Collection $roles;

    public function __construct(
        #[Column(length: 255)]
        public string $name,
    ) {
        $this->roles = new ArrayCollection();
    }

    public function addRole(EagerSlcRole $role): void
    {
        $this->roles->add($role);
    }
}

#[Entity]
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class EagerSlcRole
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;

    public function __construct(
        #[Cache(usage: 'NONSTRICT_READ_WRITE')]
        #[ManyToOne(targetEntity: EagerSlcUser::class, inversedBy: 'roles')]
        #[JoinColumn(nullable: false)]
        public EagerSlcUser $user,
        #[Column(length: 255)]
        public string $name,
    ) {
    }
}

#[Entity]
class EagerSlcOther
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;

    public function __construct(
        #[Column(length: 255)]
        public string $name,
    ) {
    }
}
