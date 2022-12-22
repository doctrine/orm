<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

class LazyEagerCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchemaForModels(
            LazyEagerCollectionUser::class,
            LazyEagerCollectionAddress::class,
            LazyEagerCollectionPhone::class
        );
    }

    public function testRefreshRefreshesBothLazyAndEagerCollections(): void
    {
        $user       = new LazyEagerCollectionUser();
        $user->data = 'Guilherme';

        $ph       = new LazyEagerCollectionPhone();
        $ph->data = '12345';
        $user->addPhone($ph);

        $ad       = new LazyEagerCollectionAddress();
        $ad->data = '6789';
        $user->addAddress($ad);

        $this->_em->persist($user);
        $this->_em->persist($ad);
        $this->_em->persist($ph);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(LazyEagerCollectionUser::class, $user->id);
        $ph   = $user->phones[0];
        $ad   = $user->addresses[0];

        $ph->data = 'abc';
        $ad->data = 'def';

        $this->_em->refresh($user);

        self::assertSame('12345', $ph->data);
        self::assertSame('6789', $ad->data);
    }
}

/**
 * @Entity
 */
class LazyEagerCollectionUser
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $data;

    /**
     * @ORM\OneToMany(targetEntity="LazyEagerCollectionPhone", cascade={"refresh"}, fetch="EAGER", mappedBy="user")
     *
     * @var LazyEagerCollectionPhone[]
     */
    public $phones;

    /**
     * @ORM\OneToMany(targetEntity="LazyEagerCollectionAddress", cascade={"refresh"}, mappedBy="user")
     *
     * @var LazyEagerCollectionAddress[]
     */
    public $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->phones    = new ArrayCollection();
    }

    public function addPhone(LazyEagerCollectionPhone $phone): void
    {
        $phone->user    = $this;
        $this->phones[] = $phone;
    }

    public function addAddress(LazyEagerCollectionAddress $address): void
    {
        $address->user     = $this;
        $this->addresses[] = $address;
    }
}

/** @Entity */
class LazyEagerCollectionPhone
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $data;

    /**
     * @ORM\ManyToOne(targetEntity="LazyEagerCollectionUser", inversedBy="phones")
     *
     * @var LazyEagerCollectionUser
     */
    public $user;
}

/** @Entity */
class LazyEagerCollectionAddress
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $data;

    /**
     * @ORM\ManyToOne(targetEntity="LazyEagerCollectionUser", inversedBy="addresses")
     *
     * @var LazyEagerCollectionUser
     */
    public $user;
}
