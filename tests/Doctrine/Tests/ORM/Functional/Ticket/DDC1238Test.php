<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1238
 */
class DDC1238Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1238User::class),
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testIssue()
    {
        $user = new DDC1238User;
        $user->setName("test");

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $userId = $user->getId();
        $this->em->clear();

        $user = $this->em->getReference(DDC1238User::class, $userId);
        $this->em->clear();

        $userId2 = $user->getId();
        self::assertEquals($userId, $userId2, "This proxy can still be initialized.");
    }

    public function testIssueProxyClear()
    {
        $user = new DDC1238User;
        $user->setName("test");

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        // force proxy load, getId() doesn't work anymore
        $user->getName();
        $userId = $user->getId();
        $this->em->clear();

        $user = $this->em->getReference(DDC1238User::class, $userId);
        $this->em->clear();

        $user2 = $this->em->getReference(DDC1238User::class, $userId);

        // force proxy load, getId() doesn't work anymore
        $user->getName();
        self::assertNull($user->getId(), "Now this is null, we already have a user instance of that type");
    }
}

/**
 * @Entity
 */
class DDC1238User
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column
     * @var string
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

