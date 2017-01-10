<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1461
 */
class DDC1461Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1461TwitterAccount::class),
                $this->em->getClassMetadata(DDC1461User::class)
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testChangeDetectionDeferredExplicit()
    {
        $user = new DDC1461User;
        $this->em->persist($user);
        $this->em->flush();

        self::assertEquals(\Doctrine\ORM\UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($user, \Doctrine\ORM\UnitOfWork::STATE_NEW), "Entity should be managed.");
        self::assertEquals(\Doctrine\ORM\UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($user), "Entity should be managed.");

        $acc = new DDC1461TwitterAccount;
        $user->twitterAccount = $acc;

        $this->em->persist($user);
        $this->em->flush();

        $user = $this->em->find(get_class($user), $user->id);
        self::assertNotNull($user->twitterAccount);
    }
}

/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class DDC1461User
{
    /**
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(type="integer")
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC1461TwitterAccount", orphanRemoval=true, fetch="EAGER", cascade = {"persist"}, inversedBy="user")
     * @var TwitterAccount
     */
    public $twitterAccount;
}

/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class DDC1461TwitterAccount
{
    /**
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(type="integer")
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC1461User", fetch="EAGER")
     */
    public $user;
}
