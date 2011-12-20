<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC440Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC440Phone'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC440Client')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    /**
     * @group DDC-440
     */
    public function testOriginalEntityDataEmptyWhenProxyLoadedFromTwoAssociations()
    {


        /* The key of the problem is that the first phone is fetched via two association, main_phone and phones.
         *
         * You will notice that the original_entity_datas are not loaded for the first phone. (They are for the second)
         *
         * In the Client entity definition, if you define the main_phone relation after the phones relation, both assertions pass.
         * (for the sake or this test, I defined the main_phone relation before the phones relation)
         *
         */

        //Initialize some data
        $client = new DDC440Client;
        $client->setName('Client1');

        $phone = new DDC440Phone;
        $phone->setNumber('418 111-1111');
        $phone->setClient($client);

        $phone2 = new DDC440Phone;
        $phone2->setNumber('418 222-2222');
        $phone2->setClient($client);

        $client->setMainPhone($phone);

        $this->_em->persist($client);
        $this->_em->flush();
        $id = $client->getId();
        $this->_em->clear();

        $uw = $this->_em->getUnitOfWork();
        $client = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC440Client', $id);
        $clientPhones = $client->getPhones();
        $p1 = $clientPhones[0];
        $p2 = $clientPhones[1];

        // Test the first phone.  The assertion actually failed because original entity data is not set properly.
        // This was because it is also set as MainPhone and that one is created as a proxy, not the
        // original object when the find on Client is called. However loading proxies did not work correctly.
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC440Phone', $p1);
        $originalData = $uw->getOriginalEntityData($p1);
        $this->assertEquals($phone->getNumber(), $originalData['number']);


        //If you comment out previous test, this one should pass
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC440Phone', $p2);
        $originalData = $uw->getOriginalEntityData($p2);
        $this->assertEquals($phone2->getNumber(), $originalData['number']);
    }

}

/**
 * @Entity
 * @Table(name="phone")
 */
class DDC440Phone
{

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ManyToOne(targetEntity="DDC440Client",inversedBy="phones")
     * @JoinColumns({
     *   @JoinColumn(name="client_id", referencedColumnName="id")
     * })
     */
    protected $client;
    /**
     * @Column(name="phonenumber", type="string")
     */
    protected $number;

    public function setNumber($value)
    {
        $this->number = $value;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setClient(DDC440Client $value, $update_inverse=true)
    {
        $this->client = $value;
        if ($update_inverse) {
            $value->addPhone($this);
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

}

/**
 * @Entity
 * @Table(name="client")
 */
class DDC440Client
{

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @OneToOne(targetEntity="DDC440Phone", fetch="EAGER")
     * @JoinColumns({
     *   @JoinColumn(name="main_phone_id", referencedColumnName="id",onDelete="SET NULL")
     * })
     */
    protected $main_phone;
    /**
     * @OneToMany(targetEntity="DDC440Phone", mappedBy="client", cascade={"persist", "remove"}, fetch="EAGER")
     * @orderBy({"number"="ASC"})
     */
    protected $phones;
    /**
     * @Column(name="name", type="string")
     */
    protected $name;

    public function __construct()
    {

    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addPhone(DDC440Phone $value)
    {
        $this->phones[] = $value;
        $value->setClient($this, false);
    }

    public function getPhones()
    {
        return $this->phones;
    }

    public function setMainPhone(DDC440Phone $value)
    {
        $this->main_phone = $value;
    }

    public function getMainPhone()
    {
        return $this->main_phone;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

}
