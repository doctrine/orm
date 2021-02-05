<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC588Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC588Site::class),
            ]
        );
    }

    public function testIssue(): void
    {
        $site = new DDC588Site('Foo');

        $this->_em->persist($site);
        $this->_em->flush();
        // Following should not result in exception
        $this->_em->refresh($site);

        $this->addToAssertionCount(1);
    }
}

/**
 * @Entity
 */
class DDC588Site
{
    /**
     * @Id
     * @Column(type="integer", name="site_id")
     * @GeneratedValue
     */
    public $id;

    /** @Column(type="string", length=45) */
    protected $name = null;

    public function __construct($name = '')
    {
        $this->name = $name;
    }
}
