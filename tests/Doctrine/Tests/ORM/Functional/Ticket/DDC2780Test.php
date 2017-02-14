<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-2780
 */
class DDC2780Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2780User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2780Project')
        ));
    }

    /**
     * Verifies that IS [NOT] NULL can be used on join aliases
     */
    public function testIssue()
    {
        $user          = new DDC2780User;
        $project       = new DDC2780Project;

        $user->project = $project;

        $this->_em->persist($project);
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('user')
            ->from(__NAMESPACE__ . '\DDC2780User', 'user')
            ->leftJoin('user.project', 'project')
            ->where('project IS NOT NULL');

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        $this->assertInstanceOf(__NAMESPACE__ . '\\DDC2780User', $result);
    }
}

/**
 * @Entity
 */
class DDC2780User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2780Project
     *
     * @ManyToOne(targetEntity="DDC2780Project")
     */
    public $project;
}

/** @Entity */
class DDC2780Project
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2780User[]
     *
     * @OneToMany(targetEntity="DDC2780User", mappedBy="project")
     */
    public $users;


    /** Constructor */
    public function __construct() {
        $this->users = new ArrayCollection();
    }
}