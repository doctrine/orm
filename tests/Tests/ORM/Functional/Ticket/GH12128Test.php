<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH12128Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH12128Group::class, GH12128Item::class, GH12128User::class);
    }

    public function testMemberOfWithAnAssociationInTheCompositeIdentifier(): void
    {
        $group      = new GH12128Group(1);
        $otherGroup = new GH12128Group(2);
        $item       = new GH12128Item($group, 1);
        $user       = new GH12128User(1);
        $user->items->add($item);

        $this->_em->persist($group);
        $this->_em->persist($otherGroup);
        $this->_em->persist($item);
        $this->_em->persist(new GH12128Item($group, 2));
        $this->_em->persist(new GH12128Item($otherGroup, 1));
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQuery('SELECT i FROM ' . GH12128Item::class . ' i WHERE :user MEMBER OF i.users')
            ->setParameter('user', $user)
            ->getSingleResult();

        self::assertInstanceOf(GH12128Item::class, $result);
        self::assertSame($item->group->id, $result->group->id);
        self::assertSame($item->itemIndex, $result->itemIndex);
    }
}

#[ORM\Entity]
class GH12128Group
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $id,
    ) {
    }
}

#[ORM\Entity]
class GH12128Item
{
    /** @var Collection<int, GH12128User> */
    #[ORM\ManyToMany(targetEntity: GH12128User::class, mappedBy: 'items')]
    public Collection $users;

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(name: 'group_id')]
        public GH12128Group $group,
        #[ORM\Id]
        #[ORM\Column(name: 'item_index')]
        public int $itemIndex,
    ) {
        $this->users = new ArrayCollection();
    }
}

#[ORM\Entity]
class GH12128User
{
    /** @var Collection<int, GH12128Item> */
    #[ORM\ManyToMany(targetEntity: GH12128Item::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'item_group_id', referencedColumnName: 'group_id')]
    #[ORM\InverseJoinColumn(name: 'item_index', referencedColumnName: 'item_index')]
    public Collection $items;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $id,
    ) {
        $this->items = new ArrayCollection();
    }
}
