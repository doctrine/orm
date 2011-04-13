<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-992
 */
class DDC992Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC992Role'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC992Parent'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC992Child'),
            ));
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

        $this->_em->persist($role);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $child = $this->_em->getRepository(get_class($role))->find($child->roleID);
        $parents = count($child->extends);
        $this->assertEquals(1, $parents);
        foreach ($child->extends AS $parent) {
            $this->assertEquals($role->getRoleID(), $parent->getRoleID());
        }
    }

    public function testOneToManyChild()
    {
        $parent = new DDC992Parent();
        $child = new DDC992Child();
        $child->parent = $parent;
        $parent->childs[] = $child;

        $this->_em->persist($parent);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $parentRepository = $this->_em->getRepository(get_class($parent));
        $childRepository = $this->_em->getRepository(get_class($child));

        $parent = $parentRepository->find($parent->id);
        $this->assertEquals(1, count($parent->childs));
        $this->assertEquals(0, count($parent->childs[0]->childs()));

        $child = $parentRepository->findOneBy(array("id" => $child->id));
        $this->assertSame($parent->childs[0], $child);

        $this->_em->clear();

        $child = $parentRepository->find($child->id);
        $this->assertEquals(0, count($child->childs));

        $this->_em->clear();

        $child = $childRepository->find($child->id);
        $this->assertEquals(0, count($child->childs));
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
     * @Column (name="name", type="string", length="45")
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