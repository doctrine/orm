<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * @group DDC-1238
 */
class DDC1238Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1238User::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    public function testIssue() : void
    {
        $user = new DDC1238User();
        $user->setName('test');

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $userId = $user->getId();
        $this->em->clear();

        $user = $this->em->getReference(DDC1238User::class, $userId);
        $this->em->clear();

        $userId2 = $user->getId();
        self::assertEquals($userId, $userId2, 'This proxy can still be initialized.');
    }

    public function testIssueProxyClear() : void
    {
        $user = new DDC1238User();
        $user->setName('test');

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        // force proxy load, getId() doesn't work anymore
        $user->getName();
        $userId = $user->getId();
        $this->em->clear();

        /** @var DDC1238User|GhostObjectInterface $user */
        $user = $this->em->getReference(DDC1238User::class, $userId);

        $this->em->clear();

        /** @var DDC1238User|GhostObjectInterface $user2 */
        $user2 = $this->em->getReference(DDC1238User::class, $userId);

        $user->initializeProxy();

        self::assertIsInt($user->getId(), 'Even if a proxy is detached, it should still have an identifier');

        $user2->initializeProxy();

        self::assertIsInt($user2->getId(), 'The managed instance still has an identifier');
    }
}

/**
 * @ORM\Entity
 */
class DDC1238User
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column
     *
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
