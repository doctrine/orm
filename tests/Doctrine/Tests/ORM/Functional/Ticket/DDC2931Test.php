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

        // After some debugging, I found that the issue came from
        // [`UnitOfWork#createEntity`](https://github.com/doctrine/doctrine2/blob/bba5ec27fbbe35224be48878a0c92827ef2f9733/lib/Doctrine/ORM/UnitOfWork.php#L2512-L2528) with #406 (DCOM-96).
        // When initializing the proxy for `$first` (during the `DDC2931User#getRank()` call), the ORM attempts to fetch also `$second` again
        // via [`ObjectHydrator#getEntity`](https://github.com/doctrine/doctrine2/blob/bba5ec27fbbe35224be48878a0c92827ef2f9733/lib/Doctrine/ORM/Internal/Hydration/ObjectHydrator.php#L280)`,
        // but the hint `doctrine.refresh.entity` contains the initialized proxy, while the identifier passed down
        // is the identifier of `$second` (not a proxy).
        // `UnitOfWork#createQuery` does some comparisons and detects that in fact, the two objects don't correspond,
        // and therefore marks the entity as "to be detached"

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
        //$id = $this->parent->id;
        //$hash = spl_object_hash($this);
        return 1 + ($this->parent ? $this->parent->getRank() : 0);
    }

    public function __wakeup()
    {
        echo 'foo';
    }
}
