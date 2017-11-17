<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH6829Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6829Contact::class),
                $this->_em->getClassMetadata(GH6829Source::class)
            ]
        );

        $source = new GH6829Source();
        $source->id = 'cck';
        $this->_em->persist($source);

        $contact = new GH6829Contact();
        $contact->sources = new ArrayCollection([$source]);
        $contact->name = 'name 1';
        $this->_em->persist($contact);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCollectionUpdate()
    {
        $contact = $this->_em->getRepository(GH6829Contact::class)->findOneBy([]);
        $contact->name = 'name 2';
        $this->_em->flush();

        $sources = $contact->sources->toArray();
        $contact->sources = new ArrayCollection($sources);
        $this->_em->flush();

        $this->assertSame(true, true);
    }
}

/**
 * @Entity
 */
class GH6829Contact
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @ManyToMany(targetEntity="GH6829Source")
     */
    public $sources;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
    }
}

/**
 * @Entity
 */
class GH6829Source
{
    /**
     * @Id
     * @Column(type="string")
     */
    public $id;
}
