<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-992
 */
class DDC992Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC992Role::class),
                $this->em->getClassMetadata(DDC992Parent::class),
                $this->em->getClassMetadata(DDC992Child::class),
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testIssue()
    {
        $role = new DDC992Role();
        $role->name = "Parent";
        $child = new DDC992Role();
        $child->name = "child";

        $role->extendedBy[] = $child;
        $child->extends[] = $role;

        $this->em->persist($role);
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        $child = $this->em->getRepository(get_class($role))->find($child->roleID);
        $parents = count($child->extends);
        self::assertEquals(1, $parents);
        foreach ($child->extends AS $parent) {
            self::assertEquals($role->getRoleID(), $parent->getRoleID());
        }
    }

    public function testOneToManyChild()
    {
        $parent = new DDC992Parent();
        $child = new DDC992Child();
        $child->parent = $parent;
        $parent->childs[] = $child;

        $this->em->persist($parent);
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        $parentRepository = $this->em->getRepository(get_class($parent));
        $childRepository = $this->em->getRepository(get_class($child));

        $parent = $parentRepository->find($parent->id);
        self::assertEquals(1, count($parent->childs));
        self::assertEquals(0, count($parent->childs[0]->childs()));

        $child = $parentRepository->findOneBy(["id" => $child->id]);
        self::assertSame($parent->childs[0], $child);

        $this->em->clear();

        $child = $parentRepository->find($child->id);
        self::assertEquals(0, count($child->childs));

        $this->em->clear();

        $child = $childRepository->find($child->id);
        self::assertEquals(0, count($child->childs));
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({"child" = "DDC992Child", "parent" = "DDC992Parent"})
 */
class DDC992Parent
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;
    /** @ManyToOne(targetEntity="DDC992Parent", inversedBy="childs") */
    public $parent;
    /** @OneToMany(targetEntity="DDC992Child", mappedBy="parent") */
    public $childs;
}

/**
 * @Entity
 */
class DDC992Child extends DDC992Parent
{
    public function childs()
    {
        return $this->childs;
    }
}

/**
 * @Entity
 */
class DDC992Role
{
    public function getRoleID()
    {
        return $this->roleID;
    }

    /**
     *  @Id  @Column(name="roleID", type="integer")
     *  @GeneratedValue(strategy="AUTO")
     */
    public $roleID;
    /**
     * @Column (name="name", type="string", length=45)
     */
    public $name;
    /**
     * @ManyToMany (targetEntity="DDC992Role", mappedBy="extends")
     */
    public $extendedBy;
    /**
     * @ManyToMany (targetEntity="DDC992Role", inversedBy="extendedBy")
     * @JoinTable (name="RoleRelations",
     *      joinColumns={@JoinColumn(name="roleID", referencedColumnName="roleID")},
     *      inverseJoinColumns={@JoinColumn(name="extendsRoleID", referencedColumnName="roleID")}
     *      )
     */
    public $extends;

    public function __construct() {
        $this->extends = new ArrayCollection;
        $this->extendedBy = new ArrayCollection;
    }
}
