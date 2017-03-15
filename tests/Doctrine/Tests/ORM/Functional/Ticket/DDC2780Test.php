<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

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

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC2780User::class),
                $this->em->getClassMetadata(DDC2780Project::class)
            ]
        );
    }

    /**
     * Verifies that IS [NOT] NULL can be used on join aliases
     */
    public function testIssue()
    {
        $user    = new DDC2780User;
        $project = new DDC2780Project;

        $user->project = $project;

        $this->em->persist($project);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $result = $this->em->createQueryBuilder()
            ->select('user')
            ->from(DDC2780User::class, 'user')
            ->leftJoin('user.project', 'project')
            ->where('project IS NOT NULL')
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(DDC2780User::class, $result);
    }
}

/**
 * @ORM\Entity
 */
class DDC2780User
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC2780Project")
     *
     * @var DDC2780Project
     */
    public $project;
}

/** @ORM\Entity */
class DDC2780Project
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC2780User", mappedBy="project")
     *
     * @var DDC2780User[]
     */
    public $users;

    /** Constructor */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}

