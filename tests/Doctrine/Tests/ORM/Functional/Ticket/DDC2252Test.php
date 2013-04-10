<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-2252
 */
class DDC2252Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $user;
    private $merchant;
    private $membership;
    private $privileges = array();

    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2252User'),
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2252Privilege'),
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2252Membership'),
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2252MerchantAccount'),
        ));

        $this->loadFixtures();
    }

    public function loadFixtures()
    {
        $this->user         = new DDC2252User;
        $this->merchant     = new DDC2252MerchantAccount;
        $this->membership   = new DDC2252Membership($this->user, $this->merchant);

        $this->privileges[] = new DDC2252Privilege;
        $this->privileges[] = new DDC2252Privilege;
        $this->privileges[] = new DDC2252Privilege;

        $this->membership->addPrivilege($this->privileges[0]);
        $this->membership->addPrivilege($this->privileges[1]);
        $this->membership->addPrivilege($this->privileges[2]);

        $this->_em->persist($this->user);
        $this->_em->persist($this->merchant);
        $this->_em->persist($this->privileges[0]);
        $this->_em->persist($this->privileges[1]);
        $this->_em->persist($this->privileges[2]);
        $this->_em->flush();

        $this->_em->persist($this->membership);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testIssue()
    {
        $identifier = array(
            'merchantAccount' => $this->merchant->getAccountid(),
            'userAccount'     => $this->user->getUid(),
        );

        $class      = 'Doctrine\Tests\ORM\Functional\Ticket\DDC2252Membership';
        $membership = $this->_em->find($class, $identifier);

        $this->assertInstanceOf($class, $membership);
        $this->assertCount(3, $membership->getPrivileges());

        $membership->getPrivileges()->remove(2);
        $this->_em->persist($membership);
        $this->_em->flush();
        $this->_em->clear();

        $membership = $this->_em->find($class, $identifier);

        $this->assertInstanceOf($class, $membership);
        $this->assertCount(2, $membership->getPrivileges());

        $membership->getPrivileges()->clear();
        $this->_em->persist($membership);
        $this->_em->flush();
        $this->_em->clear();

        $membership = $this->_em->find($class, $identifier);

        $this->assertInstanceOf($class, $membership);
        $this->assertCount(0, $membership->getPrivileges());

        $membership->addPrivilege($privilege3 = new DDC2252Privilege);
        $this->_em->persist($privilege3);
        $this->_em->persist($membership);
        $this->_em->flush();
        $this->_em->clear();

        $membership = $this->_em->find($class, $identifier);

        $this->assertInstanceOf($class, $membership);
        $this->assertCount(1, $membership->getPrivileges());
    }
}

/**
 * @Entity()
 * @Table(name="ddc2252_acl_privilege")
 */
class DDC2252Privilege
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $privilegeid;

    public function getPrivilegeid()
    {
        return $this->privilegeid;
    }
}

/**
 * @Entity
 * @Table(name="ddc2252_mch_account")
 */
class DDC2252MerchantAccount
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $accountid = 111;

    public function getAccountid()
    {
        return $this->accountid;
    }
}

/**
 * @Entity
 * @Table(name="ddc2252_user_account")
 */
class DDC2252User {
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $uid = 222;

    /**
     * @OneToMany(targetEntity="DDC2252Membership", mappedBy="userAccount", cascade={"persist"})
     * @JoinColumn(name="uid", referencedColumnName="uid")
     */
    protected $memberships;

    public function __construct()
    {
        $this->memberships = new ArrayCollection;
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function getMemberships()
    {
        return $this->memberships;
    }

    public function addMembership(DDC2252Membership $membership)
    {
        $this->memberships[] = $membership;
    }
}

/**
 * @Entity
 * @Table(name="ddc2252_mch_account_member")
 * @HasLifecycleCallbacks
 */
class DDC2252Membership
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC2252User", inversedBy="memberships")
     * @JoinColumn(name="uid", referencedColumnName="uid")
     */
    protected $userAccount;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC2252MerchantAccount")
     * @JoinColumn(name="mch_accountid", referencedColumnName="accountid")
     */
    protected $merchantAccount;

    /**
     * @ManyToMany(targetEntity="DDC2252Privilege", indexBy="privilegeid")
     * @JoinTable(name="ddc2252_user_mch_account_privilege",
     *   joinColumns={
     *       @JoinColumn(name="mch_accountid", referencedColumnName="mch_accountid"),
     *       @JoinColumn(name="uid", referencedColumnName="uid")
     *   },
     *   inverseJoinColumns={
     *       @JoinColumn(name="privilegeid", referencedColumnName="privilegeid")
     *   }
     * )
     */
    protected $privileges;

    public function __construct(DDC2252User $user, DDC2252MerchantAccount $merchantAccount)
    {
        $this->userAccount      = $user;
        $this->merchantAccount  = $merchantAccount;
        $this->privileges       = new ArrayCollection();
    }

    public function addPrivilege($privilege)
    {
        $this->privileges[] = $privilege;
    }

    public function getPrivileges()
    {
        return $this->privileges;
    }
}