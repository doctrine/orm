<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use function get_class;

/**
 * @group DDC-1461
 */
class DDC1461Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1461TwitterAccount::class),
                    $this->em->getClassMetadata(DDC1461User::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    public function testChangeDetectionDeferredExplicit() : void
    {
        $user = new DDC1461User();
        $this->em->persist($user);
        $this->em->flush();

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($user, UnitOfWork::STATE_NEW), 'Entity should be managed.');
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($user), 'Entity should be managed.');

        $acc                  = new DDC1461TwitterAccount();
        $user->twitterAccount = $acc;

        $this->em->persist($user);
        $this->em->flush();

        $user = $this->em->find(get_class($user), $user->id);
        self::assertNotNull($user->twitterAccount);
    }
}

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class DDC1461User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity=DDC1461TwitterAccount::class, orphanRemoval=true, fetch="EAGER", cascade = {"persist"}, inversedBy="user")
     *
     * @var TwitterAccount
     */
    public $twitterAccount;
}

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class DDC1461TwitterAccount
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC1461User::class, fetch="EAGER") */
    public $user;
}
