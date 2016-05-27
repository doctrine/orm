<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\DoctrineValueObject;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * @group DDC-2016
 */
class DDC2016Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * Verifies that when eager loading is triggered, proxies are kept managed.
     *
     * The problem resides in the refresh hint passed to {@see \Doctrine\ORM\UnitOfWork::createEntity},
     * which, as of DDC-1734, causes the proxy to be marked as un-managed.
     * The check against the identity map only uses the identifier hash and the passed in class name, and
     * does not take into account the fact that the set refresh hint may be for an entity of a different
     * type from the one passed to {@see \Doctrine\ORM\UnitOfWork::createEntity}
     *
     * As a result, a refresh requested for an entity `Foo` with identifier `123` may cause a proxy
     * of type `Bar` with identifier `123` to be marked as un-managed.
     */
    public function testIssue()
    {
        $metadata = $this->_em->getClassMetadata(DDC2016User::CLASS_NAME);
        $uow      = $this->_em->getUnitOfWork();

        $username = new DDC2016Username('validUser');
        $user     = new DDC2016User($username);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getRepository(DDC2016User::CLASS_NAME)->find($user->id);
        $this->assertInstanceOf(DDC2016User::CLASS_NAME, $user);

        /*
         * Call the getter which validate the DB value with Value Object and set the doctrine property to
         * avoid duplicate validation and continuously re-creating the Value Object.
         *
         * Issue:
         *
         * Because we set the property, computeChangeSet will detect as a change and will update the entity.
         */
        $username = $user->getUsername();
        $this->assertInstanceOf(DDC2016Username::CLASS_NAME, $username);

        $uow->computeChangeSet($metadata, $user);
        $changeset = $uow->getEntityChangeSet($user);

        /*
         * User not changed, just called the getter, which create and validate! the data from db.
         * Unfortunately, doctrine detect as a change and will mark property as changed.
         */
        $this->assertNotEmpty($changeset, 'Changeset not empty, but should!');
    }

    public function testDoctrineNotMarkAsChangedIfVOImplementsDoctrineValueObjectInterface()
    {
        $metadata = $this->_em->getClassMetadata(DC2016UserWithVo::CLASS_NAME);
        $uow      = $this->_em->getUnitOfWork();

        $txtUser  = 'validUser';
        $username = new DC2016UsernameVo($txtUser);
        $user     = new DC2016UserWithVo($username);
        $this->_em->persist($user);

        $uow->computeChangeSet($metadata, $user);
        $changeSet = $uow->getEntityChangeSet($user);

        /*
         * Changeset should hold the ValueObject.
         */
        $this->assertInstanceOf(DC2016UsernameVo::CLASS_NAME, $changeSet['username'][1]);

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getRepository(DC2016UserWithVo::CLASS_NAME)->find($user->id);
        $this->assertInstanceOf(DC2016UserWithVo::CLASS_NAME, $user);
        $this->assertEquals($txtUser, (string)$user->getUsername());

        $username = $user->getUsername();
        $this->assertInstanceOf(DC2016UsernameVo::CLASS_NAME, $username);

        $uow->computeChangeSet($metadata, $user);
        $changeSet = $uow->getEntityChangeSet($user);

        /*
         * We called the getter which set the Doctrine property to VO. Because of VO implements DoctrineValueObject,
         * $changeSet should be an empty array.
         */
        $this->assertEmpty($changeSet, 'Changeset should empty now, because we implement DoctrineValueObject!');
    }

    public function testUsernameGetUsernameReturnUsername()
    {
        $usernameString = 'validUserName';

        $username = new DDC2016Username($usernameString);

        $this->assertEquals($usernameString, $username->getUsername());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUsernameThrowExceptionOnNonString()
    {
        new DDC2016Username(123);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testUsernameThrowExceptionOnInvalidUsername()
    {
        new DDC2016Username('invalidUser-INVALID');
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC2016User::CLASS_NAME),
            $this->_em->getClassMetadata(DC2016UserWithVo::CLASS_NAME),
        ));
    }

    protected function tearDown()
    {
        $this->_schemaTool->dropSchema(array(
            $this->_em->getClassMetadata(DDC2016User::CLASS_NAME),
            $this->_em->getClassMetadata(DC2016UserWithVo::CLASS_NAME),
        ));

        parent::tearDown();
    }
}

/**
 * @Entity
 * @MappedSuperclass()
 */
class DDC2016User
{
    const CLASS_NAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2016Username
     *
     * @Column(type="string", length=128, nullable=false)
     */
    public $username;

    /** Constructor
     *
     * @param DDC2016Username $username
     */
    public function __construct(DDC2016Username $username)
    {
        $this->username = $username;
    }

    /**
     * @return DDC2016Username
     */
    public function getUsername()
    {
        return ($this->username instanceof DDC2016Username)
            ? $this->username
            : $this->username = new DDC2016Username($this->username);
    }

    /**
     * @param DDC2016Username $username
     *
     * @return $this
     */
    public function setUsername(DDC2016Username $username)
    {
        $this->username = $username;

        return $this;
    }
}

/**
 * Username ValueObject
 */
class DDC2016Username
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    protected $username;

    /**
     * @param string $username
     *
     * @throws \InvalidArgumentException If $username is not a string.
     * @throws \UnexpectedValueException If $username is not acceptable.
     */
    public function __construct($username)
    {
        if ( ! is_string($username)) {
            throw new \InvalidArgumentException(
                sprintf('Username should be a string. [received: %s]', gettype($username))
            );
        }

        if (preg_match('/.*-INVALID$/', $username)) {
            throw new \UnexpectedValueException(
                sprintf('Username not acceptable. [username: %s]', $username)
            );
        }

        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function __toString()
    {
        return $this->getUsername();
    }
}

class DC2016UsernameVo extends DDC2016Username implements DoctrineValueObject
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function equals($value)
    {
        if ($value instanceof DoctrineValueObject) {
            return $this->getUsername() == (string)$value;
        }

        return $this->getUsername() == $value;
    }
}

/**
 * @Entity()
 */
class DC2016UserWithVo
{
    const CLASS_NAME =  __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2016Username
     *
     * @Column(type="string", length=128, nullable=false)
     */
    public $username;

    /** Constructor
     *
     * @param DDC2016Username $username
     */
    public function __construct(DDC2016Username $username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return ($this->username instanceof DC2016UsernameVo)
            ? $this->username
            : $this->username = new DC2016UsernameVo($this->username);
    }

    /**
     * @param DDC2016Username $username
     *
     * @return $this
     */
    public function setUsername(DDC2016Username $username)
    {
        $this->username = $username;

        return $this;
    }
}
