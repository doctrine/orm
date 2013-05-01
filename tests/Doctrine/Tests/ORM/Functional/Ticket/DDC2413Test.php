<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2413
 */
class DDC2413Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    public function testTicket()
    {
        if (!in_array($this->_em->getConnection()->getDatabasePlatform()->getName(), array('mysql', 'pgsql', 'sqlsrv'))) {
            $this->markTestSkipped("This test is for relational databases.");
        }

        $schemaTool = new SchemaTool($this->_em);

        $metadatas = array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2413DomainEntity'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2413GroupEntity'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2413UserEntity'),
        );

        $schema = $schemaTool->getSchemaFromMetadata($metadatas);

        $this->assertTrue($schema->hasTable('DDC2413GroupEntity_DDC2413UserEntity'), "Table DDC2413GroupEntity_DDC2413UserEntity should exist.");
        $this->assertEquals(array('group_name', 'domain', 'user_name'), $schema->getTable('DDC2413GroupEntity_DDC2413UserEntity')->getPrimaryKeyColumns(), "Wrong DDC2413GroupEntity_DDC2413UserEntity Primary Keys.");

        $schemaTool->createSchema($metadatas);

        $domain = new DDC2413DomainEntity('local');
        $group  = new DDC2413GroupEntity('foobar', $domain);
        $user   = new DDC2413UserEntity('anonymous', $domain);
        $group->getUsers()->clear();
        $group->getUsers()->add($user);

        $this->_em->persist($domain);
        $this->_em->persist($group);
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $foundUser = $this->_em->find(__NAMESPACE__ . '\DDC2413UserEntity', array('name' => 'anonymous', 'domain' => 'local'));

        $this->assertEquals(1, $foundUser->getGroups()->count());
        $this->assertEquals($group->getName(), $foundUser->getGroups()->first()->getName());
    }
}

/**
 * Class DDC2413DomainEntity
 *
 * @Entity
 * @Table(name="DDC2413DomainEntity")
 */
class DDC2413DomainEntity
{
    /**
     * @var string
     *
     * @Id
     * @Column(type="string", length=22, nullable=false)
     */
    protected $name = '';

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

/**
 * Class DDC2413GroupEntity
 *
 * @Entity
 * @Table(name="DDC2413GroupEntity")
 */
class DDC2413GroupEntity
{
    /**
     * @var string
     *
     * @Id
     * @Column(type="string", length=22, nullable=false)
     */
    protected $name = '';

    /**
     * @var DDC2413DomainEntity
     *
     * @Id
     * @ManyToOne(targetEntity="DDC2413DomainEntity", fetch="LAZY")
     * @JoinColumn(name="domain", referencedColumnName="name", onDelete="CASCADE")
     */
    protected $domain;

    /**
     * @var DDC2413UserEntity[]|ArrayCollection
     *
     * @ManyToMany(targetEntity="DDC2413UserEntity", inversedBy="groups", fetch="EXTRA_LAZY", cascade="ALL")
     * @JoinTable(name="DDC2413GroupEntity_DDC2413UserEntity",
     *      joinColumns={
     * @JoinColumn(name="group_name", referencedColumnName="name", onDelete="CASCADE"),
     * @JoinColumn(name="domain", referencedColumnName="domain", onDelete="CASCADE")
     *          },
     *      inverseJoinColumns={
     * @JoinColumn(name="user_name", referencedColumnName="name", onDelete="CASCADE"),
     * @JoinColumn(name="domain", referencedColumnName="domain", onDelete="CASCADE")
     *          }
     *      )
     */
    protected $users;

    /**
     * @param string              $name
     * @param DDC2413DomainEntity $domain
     */
    public function __construct($name, DDC2413DomainEntity $domain)
    {
        $this->name   = $name;
        $this->domain = $domain;
        $this->users  = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|DDC2413UserEntity[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

/**
 * Class DDC2413UserEntity
 *
 * @Entity
 * @Table(name="DDC2413UserEntity")
 */
class DDC2413UserEntity
{
    /**
     * @var string
     *
     * @Id
     * @Column(type="string", length=22, nullable=false)
     */
    protected $name = '';

    /**
     * @var DDC2413DomainEntity
     *
     * @Id
     * @ManyToOne(targetEntity="DDC2413DomainEntity", fetch="LAZY")
     * @JoinColumn(name="domain", referencedColumnName="name", onDelete="CASCADE")
     */
    protected $domain;

    /**
     * @var DDC2413GroupEntity[]|ArrayCollection
     *
     * @ManyToMany(targetEntity="DDC2413GroupEntity", mappedBy="users", cascade="ALL", fetch="EXTRA_LAZY")
     */
    protected $groups;

    /**
     * @param string              $name
     * @param DDC2413DomainEntity $domain
     */
    public function __construct($name, DDC2413DomainEntity $domain)
    {
        $this->name   = $name;
        $this->domain = $domain;
        $this->groups = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|DDC2413GroupEntity[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

