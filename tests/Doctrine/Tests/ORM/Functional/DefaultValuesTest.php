<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests basic operations on entities with default values.
 *
 * @author robo
 */
class DefaultValuesTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DefaultValueUser::class),
                $this->em->getClassMetadata(DefaultValueAddress::class)
                ]
            );
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    /**
     * @group non-cacheable
     */
    public function testSimpleDetachMerge() {
        $user = new DefaultValueUser;
        $user->name = 'romanb';
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $userId = $user->id; // e.g. from $_REQUEST
        $user2 = $this->em->getReference(get_class($user), $userId);

        $this->em->flush();
        self::assertFalse($user2->__isInitialized__);

        $a = new DefaultValueAddress;
        $a->country = 'de';
        $a->zip = '12345';
        $a->city = 'Berlin';
        $a->street = 'Sesamestreet';

        $a->user = $user2;
        $this->em->persist($a);
        $this->em->flush();

        self::assertFalse($user2->__isInitialized__);
        $this->em->clear();

        $a2 = $this->em->find(get_class($a), $a->id);
        self::assertInstanceOf(DefaultValueUser::class, $a2->getUser());
        self::assertEquals($userId, $a2->getUser()->getId());
        self::assertEquals('Poweruser', $a2->getUser()->type);
    }

    /**
     * @group DDC-1386
     */
    public function testGetPartialReferenceWithDefaultValueNotEvaluatedInFlush()
    {
        $user = new DefaultValueUser;
        $user->name = 'romanb';
        $user->type = 'Normaluser';

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->getPartialReference(DefaultValueUser::class, $user->id);
        self::assertTrue($this->em->getUnitOfWork()->isReadOnly($user));

        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(DefaultValueUser::class, $user->id);

        self::assertEquals('Normaluser', $user->type);
    }
}


/**
 * @ORM\Entity @ORM\Table(name="defaultvalueuser")
 */
class DefaultValueUser
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(type="string")
     */
    public $name = '';
    /**
     * @ORM\Column(type="string")
     */
    public $type = 'Poweruser';
    /**
     * @ORM\OneToOne(targetEntity="DefaultValueAddress", mappedBy="user", cascade={"persist"})
     */
    public $address;

    public function getId() {return $this->id;}
}

/**
 * CmsAddress
 *
 * @ORM\Entity @ORM\Table(name="defaultvalueaddresses")
 */
class DefaultValueAddress
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $country;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $zip;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /**
     * @ORM\OneToOne(targetEntity="DefaultValueUser")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function getUser() {return $this->user;}
}
