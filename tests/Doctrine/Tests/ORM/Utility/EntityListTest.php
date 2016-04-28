<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
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
 * EntityList tests
 *
 * @author Oleg Namaka <avantprogger@gmail.com>
 */
class EntityListTest extends DoctrineTestCase
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
}
