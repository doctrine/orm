<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2931
 */
class DDC2931Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2931User'),
            ));
        } catch (\Exception $e) {
            // no action needed - schema seems to be already in place
        }
    }

    public function testIssue()
    {
        $first = new DDC2931User();
        $second = new DDC2931User();
        $third = new DDC2931User();

        $second->parent = $first;
        $third->parent  = $second;

        $this->_em->persist($first);
        $this->_em->persist($second);
        $this->_em->persist($third);

        $this->_em->flush();
        $this->_em->clear();

        // Load Entity in second order
        $second = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC2931User', $second->id);
        $this->assertSame(2, $second->getRank());
    }
}


/** @Entity */
class DDC2931User
{

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /** @OneToOne(targetEntity="DDC2931User", inversedBy="child") */
    public $parent;

    /** @OneToOne(targetEntity="DDC2931User", mappedBy="parent") */
    public $child;

    /**
     * Return Rank recursively
     * My rank is 1 + rank of my parent
     * @return integer
     */
    public function getRank()
    {
        return 1 + ($this->parent ? $this->parent->getRank() : 0);
    }
}
