<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1526
 */
class DDC1526Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1526Menu'),
        ));
    }

    public function testIssue()
    {
        $parents = array();
        for ($i = 0; $i < 9; $i++) {
            $entity = new DDC1526Menu;

            if (isset ($parents[($i % 3)])) {
                $entity->parent = $parents[($i%3)];
            }

            $this->_em->persist($entity);
            $parents[$i] = $entity;
        }
        $this->_em->flush();
        $this->_em->clear();


        $dql = "SELECT m, c
            FROM " . __NAMESPACE__ . "\DDC1526Menu m
            LEFT JOIN m.children c";
        $menus = $this->_em->createQuery($dql)->getResult();

        // All Children collection now have to be initialized
        foreach ($menus as $menu) {
            $this->assertTrue($menu->children->isInitialized());
        }
    }
}

/**
 * @Entity
 */
class DDC1526Menu
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;
    /**
     * @ManyToOne(targetEntity="DDC1526Menu", inversedBy="children")
     */
    public $parent;

    /**
     * @OneToMany(targetEntity="DDC1526Menu", mappedBy="parent")
     */
    public $children;
}
