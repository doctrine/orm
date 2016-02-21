<?php
/*
 * This file is part of the OpCart software.
 *
 * (c) 2016, OpticsPlanet, Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Utility\EntityList;
use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * class
 *
 * @copyright   2016 OpticsPlanet, Inc
 * @package
 * @author      Oleg Namaka <oleg.namaka@opticsplanet.com>
 */
class EntityListSorterTest extends DoctrineTestCase
{
    /**
     * @var  ConnectionMock
     */
    private $_connectionMock;

    /**
     * @var EntityManagerMock
     */
    private $_emMock;

    /**
     * @var  UnitOfWorkMock
     */
    private $_unitOfWork;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_connectionMock = new ConnectionMock(array(), new DriverMock());
        $this->_emMock         = EntityManagerMock::create($this->_connectionMock);
        $this->_unitOfWork     = new UnitOfWorkMock($this->_emMock);
        $this->_emMock->setUnitOfWork($this->_unitOfWork);
    }

    /**
     * Test entityListSorter
     *
     * @group utilities
     */
    public function testEntityListSorter()
    {
        $user1        = new CmsUser();
        $user1->name  = 'John';
        $email1       = new CmsEmail();
        $email1->id   = 4;
        $user1->email = $email1;

        $user2        = new CmsUser();
        $user2->name  = 'Adam';
        $email2       = new CmsEmail();
        $email2->id   = 1;
        $user2->email = $email2;

        $list1 = $list2 = array($user1, $user2);

        EntityList::sort($list1, array('name' => 'ASC'), $this->_emMock->getMetadataFactory());
        $this->assertSame($list1[0], $user2);
        $this->assertSame($list1[1], $user1);

        EntityList::sort($list1, array('name' => 'DESC'), $this->_emMock->getMetadataFactory());
        $this->assertSame($list1[0], $user1);
        $this->assertSame($list1[1], $user2);

        EntityList::sort($list2, array('email' => 'ASC'), $this->_emMock->getMetadataFactory());
        $this->assertSame($list2[0], $user2);
        $this->assertSame($list2[1], $user1);

        EntityList::sort($list2, array('email' => 'DESC'), $this->_emMock->getMetadataFactory());
        $this->assertSame($list2[0], $user1);
        $this->assertSame($list2[1], $user2);

        $user3        = new CmsUser();
        $user3->name  = 'Adam';
        $email3       = new CmsEmail();
        $email3->id    = 10;
        $user3->email = $email3;

        $list3 = array($user1, $user2, $user3);

        EntityList::sort($list3, array('name' => 'ASC', 'email' => 'DESC'), $this->_emMock->getMetadataFactory());
        $this->assertSame($list3[0], $user3);
        $this->assertSame($list3[1], $user2);
        $this->assertSame($list3[2], $user1);

        EntityList::sort($list3, array('name' => 'ASC', 'email' => 'ASC'), $this->_emMock->getMetadataFactory());
        $this->assertSame($list3[0], $user2);
        $this->assertSame($list3[1], $user3);
        $this->assertSame($list3[2], $user1);


        $list4 = array($user1, $user2);
        EntityList::sort($list4, array(), $this->_emMock->getMetadataFactory());
        $this->assertSame($list4[0], $user1);
        $this->assertSame($list4[1], $user2);

        $list5 = array($user1);
        EntityList::sort($list5, array(), $this->_emMock->getMetadataFactory());
        $this->assertSame($list5[0], $user1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEntityListSorterWithInvalidDirection()
    {
        $list = array(new \stdClass(), new \stdClass());
        EntityList::sort($list, array('someProperty' => 'DESC1'), $this->_emMock->getMetadataFactory());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEntityListSorterWithNoStringProperty()
    {
        $list = array(new \stdClass(), new \stdClass());
        EntityList::sort($list, array(true => 'DESC'), $this->_emMock->getMetadataFactory());
    }

    public function testEntityListSorterWithCompositeKey()
    {
        $entity1 = new \Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState();
        $entity2 = new \Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState();

        $list = array($entity1, $entity2);

        EntityList::sort($list, array('state' => 'DESC'), $this->_emMock->getMetadataFactory());
    }
}
