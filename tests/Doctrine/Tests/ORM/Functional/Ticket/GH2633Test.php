<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-2633
 */
class GH2633Test extends OrmFunctionalTestCase
{
    /** @var array<string, list<class-string>> */
    protected static $modelSets = [
        'gh2633' => [
            GH2633Address::class,
            GH2633User::class,
            GH2633Country::class,
        ],
    ];

    protected function setUp(): void
    {
        $this->useModelSet('gh2633');

        parent::setUp();

        $country = new GH2633Country();
        $country->name = 'USA';
        $address = new GH2633Address();
        $address->city = 'Springfield';
        $address->street = 'Evergreen Terrace';
        $address->country = $country;
        $user = new GH2633User();
        $user->name = 'Homer';
        $user->address = $address;

        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->persist($country);

        $this->_em->flush();

        $country = new GH2633Country();
        $country->name = 'GB';
        $address = new GH2633Address();
        $address->city = 'London';
        $address->street = 'Baker Street';
        $address->country = $country;
        $user = new GH2633User();
        $user->name = 'Sherlock';
        $user->address = $address;

        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->persist($country);

        $this->_em->flush();

        $this->_em->clear();
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testArgumentOrderInJoinedNativeQueryHydratedAsArray(string $query): void
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(GH2633User::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(GH2633Address::class, 'a', 'u', 'address');
        $rsm->addJoinedEntityFromClassMetadata(GH2633Country::class, 'c', 'a', 'country');

        $native = $this->_em->createNativeQuery($query, $rsm);
        $result = $native->getArrayResult();

        $this->_em->clear();

        $this->assertCount(2, $result);

        $this->assertEquals('Homer', $result[0]['name']);
        $this->assertEquals('Evergreen Terrace', $result[0]['address']['street']);
        $this->assertEquals('Springfield', $result[0]['address']['city']);
        $this->assertEquals('USA', $result[0]['address']['country']['name']);

        $this->assertEquals('Sherlock', $result[1]['name']);
        $this->assertEquals('Baker Street', $result[1]['address']['street']);
        $this->assertEquals('London', $result[1]['address']['city']);
        $this->assertEquals('GB', $result[1]['address']['country']['name']);
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testArgumentOrderInJoinedNativeQueryHydratedAsObject(string $query): void
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(GH2633User::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(GH2633Address::class, 'a', 'u', 'address');
        $rsm->addJoinedEntityFromClassMetadata(GH2633Country::class, 'c', 'a', 'country');

        $native = $this->_em->createNativeQuery($query, $rsm);
        $result = $native->getResult();

        $this->_em->clear();

        $this->assertCount(2, $result);

        $this->assertInstanceOf(GH2633User::class, $result[0]);
        $this->assertEquals('Homer', $result[0]->name);
        $this->assertInstanceOf(GH2633Address::class, $result[0]->address);
        $this->assertEquals('Evergreen Terrace', $result[0]->address->street);
        $this->assertEquals('Springfield', $result[0]->address->city);
        $this->assertInstanceOf(GH2633Country::class, $result[0]->address->country);
        $this->assertEquals('USA', $result[0]->address->country->name);

        $this->assertInstanceOf(GH2633User::class, $result[1]);
        $this->assertEquals('Sherlock', $result[1]->name);
        $this->assertInstanceOf(GH2633Address::class, $result[1]->address);
        $this->assertEquals('Baker Street', $result[1]->address->street);
        $this->assertEquals('London', $result[1]->address->city);
        $this->assertInstanceOf(GH2633Country::class, $result[1]->address->country);
        $this->assertEquals('GB', $result[1]->address->country->name);
    }

    public function queryDataProvider(): iterable
    {
        $query = 'SELECT %s FROM gh2633_users u 
            LEFT JOIN gh2633_addresses a ON (u.address_id = a.a_id) 
            LEFT JOIN gh2633_countries c ON (a.country_id = c.c_id) 
            ORDER BY u.u_id ASC';

        yield [sprintf($query, 'u.*, a.*, c.* ')];
        yield [sprintf($query, 'u.*, c.*, a.* ')];
        yield [sprintf($query, 'a.*, u.*, c.* ')];
        yield [sprintf($query, 'a.*, c.*, u.* ')];
        yield [sprintf($query, 'c.*, a.*, u.* ')];
        yield [sprintf($query, 'c.*, u.*, a.* ')];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $conn = static::$sharedConn;

        $conn->executeStatement('DELETE FROM gh2633_addresses');
        $conn->executeStatement('DELETE FROM gh2633_users');
        $conn->executeStatement('DELETE FROM gh2633_countries');
        $this->_em->clear();
    }
}

/**
 * @ORM\Table(name="gh2633_addresses")
 * @ORM\Entity
 */
class GH2633Address
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="a_id")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type="string", name="a_street") */
    public $street;

    /** @ORM\Column(type="string", name="a_city") */
    public $city;

    /** @ORM\OneToOne(targetEntity="GH2633User", mappedBy="address") */
    public $user;

    /**
     * @ORM\ManyToOne(targetEntity="GH2633Country")
     * @ORM\JoinColumn(referencedColumnName="c_id")
     */
    public $country;
}

/**
 * @ORM\Table(name="gh2633_users")
 * @ORM\Entity
 */
class GH2633User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer",name="u_id")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type="string", name="u_name") */
    public $name;

    /**
     * @ORM\OneToOne(targetEntity="GH2633Address", inversedBy="user")
     * @ORM\JoinColumn(referencedColumnName="a_id")
     */
    public $address;
}

/**
 * @ORM\Table(name="gh2633_countries")
 * @ORM\Entity
 */
class GH2633Country
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="c_id")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type="string", name="c_name") */
    public $name;
}
