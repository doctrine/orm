<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsEmployee;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1238
 */
class DDC1238Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1238User'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testIssue()
    {
        $user = new DDC1238User;
        $user->setName("test");

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $userId = $user->getId();
        $this->_em->clear();

        $user = $this->_em->getReference(__NAMESPACE__ . '\\DDC1238User', $userId);
        $this->_em->clear();

        $userId2 = $user->getId();
        $this->assertEquals($userId, $userId2, "This proxy can still be initialized.");
    }

    public function testIssueProxyClear()
    {
        $user = new DDC1238User;
        $user->setName("test");

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        // force proxy load, getId() doesn't work anymore
        $user->getName();
        $userId = $user->getId();
        $this->_em->clear();

        $user = $this->_em->getReference(__NAMESPACE__ . '\\DDC1238User', $userId);
        $this->_em->clear();

        $user2 = $this->_em->getReference(__NAMESPACE__ . '\\DDC1238User', $userId);

        // force proxy load, getId() doesn't work anymore
        $user->getName();
        $this->assertNull($user->getId(), "Now this is null, we already have a user instance of that type");
    }
}

/**
 * @Entity
 */
class DDC1238User
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column
     * @var string
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

