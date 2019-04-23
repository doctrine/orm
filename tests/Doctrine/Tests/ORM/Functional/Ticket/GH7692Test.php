<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH7692Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7692Project::class,
            GH7692Contact::class,
        ]);
    }

    public function testWrongForeignKeysInDatabaseAreHandledByDoctrine(): void
    {
        // Create a row that references missing rows
        $this->_em->getConnection()->insert('project', [
            // This composite foreign key doesn't exist
            'contact_category' => 999,
            'contact_number' => 999,
        ]);

        $projects = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\GH7692Project p')->getResult();
        $this->assertCount(1, $projects);
    }
}

/**
 * @Entity
 * @Table(name="project")
 */
class GH7692Project
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\GH7692Contact", fetch="EAGER")
     * @JoinColumns({
     *     @JoinColumn(name="contact_category", referencedColumnName="category"),
     *     @JoinColumn(name="contact_number", referencedColumnName="number")
     * })
     */
    public $contact;
}

/**
 * @Entity
 */
class GH7692Contact
{
    /**
     * @Id
     * @Column(type="integer")
     */
    public $category;

    /**
     * @Id
     * @Column(type="integer")
     */
    public $number;
}
