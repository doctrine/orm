<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH6831Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6831Contact::class),
                $this->_em->getClassMetadata(GH6831Source::class)
            ]
        );

        $source = new GH6831Source();
        $source->id = 'cck';
        $this->_em->persist($source);

        $contact = new GH6831Contact();
        $contact->sources = new ArrayCollection([$source]);
        $contact->name = 'name 1';
        $this->_em->persist($contact);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCollectionUpdate()
    {
        $contact = $this->_em->getRepository(GH6831Contact::class)->findOneBy([]);
        $contact->name = 'name 2';
        $this->_em->flush();

        $sources = $contact->sources->toArray();
        $contact->sources = new ArrayCollection($sources);
        $this->_em->flush();
        $this->_em->refresh($contact);

        $this->assertCount(1, $contact->sources);
    }
}

/**
 * @Entity
 */
class GH6831Contact
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
     * @ManyToMany(targetEntity="GH6831Source")
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
class GH6831Source
{
    /**
     * @Id
     * @Column(type="string")
     */
    public $id;
}
