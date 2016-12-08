<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1228
 * @group DDC-1226
 */
class DDC1228Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC1228User::class),
                $this->_em->getClassMetadata(DDC1228Profile::class),
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testOneToOnePersist()
    {
        $user = new DDC1228User;
        $profile = new DDC1228Profile();
        $profile->name = "Foo";
        $user->profile = $profile;

        $this->_em->persist($user);
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(DDC1228User::class, $user->id);

        $this->assertFalse($user->getProfile()->__isInitialized__, "Proxy is not initialized");
        $user->getProfile()->setName("Bar");
        $this->assertTrue($user->getProfile()->__isInitialized__, "Proxy is not initialized");

        $this->assertEquals("Bar", $user->getProfile()->getName());
        $this->assertEquals(["id" => 1, "name" => "Foo"], $this->_em->getUnitOfWork()->getOriginalEntityData($user->getProfile()));

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(DDC1228User::class, $user->id);
        $this->assertEquals("Bar", $user->getProfile()->getName());
    }

    public function testRefresh()
    {
        $user = new DDC1228User;
        $profile = new DDC1228Profile();
        $profile->name = "Foo";
        $user->profile = $profile;

        $this->_em->persist($user);
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getReference(DDC1228User::class, $user->id);

        $this->_em->refresh($user);
        $user->name = "Baz";
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(DDC1228User::class, $user->id);
        $this->assertEquals("Baz", $user->name);
    }
}

/**
 * @Entity
 */
class DDC1228User
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @Column(type="string")
     * @var string
     */
    public $name = 'Bar';

    /**
     * @OneToOne(targetEntity="DDC1228Profile")
     * @var Profile
     */
    public $profile;

    public function getProfile()
    {
        return $this->profile;
    }
}

/**
 * @Entity
 */
class DDC1228Profile
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @column(type="string")
     * @var string
     */
    public $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
