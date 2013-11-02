<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests basic operations on entities with default values.
 *
 * @author robo
 */
class DefaultValuesTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\DefaultValueUser'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\DefaultValueAddress')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testSimpleDetachMerge() {
        $user = new DefaultValueUser;
        $user->name = 'romanb';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $userId = $user->id; // e.g. from $_REQUEST
        $user2 = $this->_em->getReference(get_class($user), $userId);

        $this->_em->flush();
        $this->assertFalse($user2->__isInitialized__);

        $a = new DefaultValueAddress;
        $a->country = 'de';
        $a->zip = '12345';
        $a->city = 'Berlin';
        $a->street = 'Sesamestreet';

        $a->user = $user2;
        $this->_em->persist($a);
        $this->_em->flush();

        $this->assertFalse($user2->__isInitialized__);
        $this->_em->clear();

        $a2 = $this->_em->find(get_class($a), $a->id);
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\DefaultValueUser', $a2->getUser());
        $this->assertEquals($userId, $a2->getUser()->getId());
        $this->assertEquals('Poweruser', $a2->getUser()->type);
    }

    /**
     * @group DDC-1386
     */
    public function testGetPartialReferenceWithDefaultValueNotEvaluatedInFlush()
    {
        $user = new DefaultValueUser;
        $user->name = 'romanb';
        $user->type = 'Normaluser';

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getPartialReference('Doctrine\Tests\ORM\Functional\DefaultValueUser', $user->id);
        $this->assertTrue($this->_em->getUnitOfWork()->isReadOnly($user));

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\ORM\Functional\DefaultValueUser', $user->id);

        $this->assertEquals('Normaluser', $user->type);
    }
}


/**
 * @Entity @Table(name="defaultvalueuser")
 */
class DefaultValueUser
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(type="string")
     */
    public $name = '';
    /**
     * @Column(type="string")
     */
    public $type = 'Poweruser';
    /**
     * @OneToOne(targetEntity="DefaultValueAddress", mappedBy="user", cascade={"persist"})
     */
    public $address;

    public function getId() {return $this->id;}
}

/**
 * CmsAddress
 *
 * @Entity @Table(name="defaultvalueaddresses")
 */
class DefaultValueAddress
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     */
    public $country;

    /**
     * @Column(type="string", length=50)
     */
    public $zip;

    /**
     * @Column(type="string", length=50)
     */
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /**
     * @OneToOne(targetEntity="DefaultValueUser")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function getUser() {return $this->user;}
}
