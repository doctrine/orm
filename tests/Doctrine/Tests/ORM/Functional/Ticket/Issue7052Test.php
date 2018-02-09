<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsEmployee;

/**
 * @group issue-7052
 */
class Issue7052Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\Issue7052Child'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\Issue7052Parent'),
        ));
    }

    public function testIssue()
    {
        $parent = new Issue7052Parent();
        $parent->name = "parent test";

        $childA = new Issue7052Child();
        $childA->name = "child A";
        $childA->parent = $parent;

        $childB = new Issue7052Child();
        $childB->name = "child B";
        $childB->parent = $parent;

        $this->_em->persist($parent);
        $this->_em->persist($childA);
        $this->_em->persist($childB);
        $this->_em->flush();
        $this->_em->clear();

        $childA = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\Issue7052Child', $childA->id);

        $this->_em->clear();

        $childB = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\Issue7052Child', $childB->id);

        $parentFromChildB = $childB->parent;

        $this->assertNotNull($childA->parent->name, "Unable to get parent name after EM cleared.");
    }
}

/**
 * @Entity
 * @Table(name="issuebitonexxx_child")
 */
class Issue7052Child
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /**
     * @Column
     * @var string
     */
    public $name;

    /**
     * @ManyToOne(targetEntity="Issue7052Parent", fetch="LAZY")
     * @JoinTable(
     *   name="issuebtonexxx_parent",
     *   inverseJoinColumns={@JoinColumn(name="parent_id", referencedColumnName="id")}
     * )
     */
    public $parent;
}

/**
 * @Entity
 * @Table(name="issuebitonexxx_parent")
 */
class Issue7052Parent
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /**
     * @Column
     * @var string
     */
    public $name;
}
