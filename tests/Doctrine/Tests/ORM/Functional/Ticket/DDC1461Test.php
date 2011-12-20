<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1461
 */
class DDC1461Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1461TwitterAccount'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1461User')
            ));
        } catch(\Exception $e) {

        }
    }

    public function testChangeDetectionDeferredExplicit()
    {
        $user = new DDC1461User;
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($user, \Doctrine\ORM\UnitOfWork::STATE_NEW), "Entity should be managed.");
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($user), "Entity should be managed.");

        $acc = new DDC1461TwitterAccount;
        $user->twitterAccount = $acc;

        $this->_em->persist($user);
        $this->_em->flush();

        $user = $this->_em->find(get_class($user), $user->id);
        $this->assertNotNull($user->twitterAccount);
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