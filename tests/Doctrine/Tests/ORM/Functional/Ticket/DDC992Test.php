<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

use function get_class;

/** @group DDC-992 */
class DDC992Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC992Role::class,
            DDC992Parent::class,
            DDC992Child::class
        );
    }

    public function testIssue(): void
    {
        $role        = new DDC992Role();
        $role->name  = 'Parent';
        $child       = new DDC992Role();
        $child->name = 'child';

        $role->extendedBy[] = $child;
        $child->extends[]   = $role;

        $this->_em->persist($role);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $child = $this->_em->getRepository(get_class($role))->find($child->roleID);
        self::assertCount(1, $child->extends);
        foreach ($child->extends as $parent) {
            self::assertEquals($role->getRoleID(), $parent->getRoleID());
        }
    }

    public function testOneToManyChild(): void
    {
        $parent           = new DDC992Parent();
        $child            = new DDC992Child();
        $child->parent    = $parent;
        $parent->childs[] = $child;

        $this->_em->persist($parent);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $parentRepository = $this->_em->getRepository(get_class($parent));
        $childRepository  = $this->_em->getRepository(get_class($child));

        $parent = $parentRepository->find($parent->id);
        self::assertCount(1, $parent->childs);
        self::assertCount(0, $parent->childs[0]->childs());

        $child = $parentRepository->findOneBy(['id' => $child->id]);
        self::assertSame($parent->childs[0], $child);

        $this->_em->clear();

        $child = $parentRepository->find($child->id);
        self::assertCount(0, $child->childs);

        $this->_em->clear();

        $child = $childRepository->find($child->id);
        self::assertCount(0, $child->childs);
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({"child" = "DDC992Child", "parent" = "DDC992Parent"})
 */
class DDC992Parent
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var DDC992Parent
     * @ManyToOne(targetEntity="DDC992Parent", inversedBy="childs")
     */
    public $parent;

    /**
     * @var Collection<int, DDC992Child>
     * @OneToMany(targetEntity="DDC992Child", mappedBy="parent")
     */
    public $childs;
}

/** @Entity */
class DDC992Child extends DDC992Parent
{
    public function childs(): Collection
    {
        return $this->childs;
    }
}

/** @Entity */
class DDC992Role
{
    public function getRoleID(): int
    {
        return $this->roleID;
    }

    /**
     * @var int
     * @Id
     * @Column(name="roleID", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $roleID;

    /**
     * @var string
     * @Column(name="name", type="string", length=45)
     */
    public $name;

    /**
     * @psalm-var Collection<int, DDC992Role>
     * @ManyToMany(targetEntity="DDC992Role", mappedBy="extends")
     */
    public $extendedBy;

    /**
     * @psalm-var Collection<int, DDC992Role>
     * @ManyToMany (targetEntity="DDC992Role", inversedBy="extendedBy")
     * @JoinTable(name="RoleRelations",
     *     joinColumns={@JoinColumn(name="roleID", referencedColumnName="roleID")},
     *     inverseJoinColumns={@JoinColumn(name="extendsRoleID", referencedColumnName="roleID")},
     * )
     */
    public $extends;

    public function __construct()
    {
        $this->extends    = new ArrayCollection();
        $this->extendedBy = new ArrayCollection();
    }
}
