<?php /* vim: set et sw=4 ts=4: */
/*
 *  $Id: Doctrine.php 3754 2008-02-13 10:53:07Z romanb $
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine
 * the base class of Doctrine framework
 *
 * @package     Doctrine
 * @author      Bjarte Stien Karlsen <doctrine@bjartek.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision: 3754 $
 */
require_once 'lib/DoctrineTestInit.php';

class Orm_Component_CollectionTest extends Doctrine_OrmTestCase
{

    private $coll;

    public function setUp()
    {
        parent::setUp();
        
        $this->coll = new Doctrine_Collection('ForumUser');

        //we create a CmsUser with username as key column and add a user to it
        $cmsColl = new Doctrine_Collection('CmsUser', 'username');
        $user = new CmsUser();
        $user->username ='test';
        $cmsColl[] = $user;
        $this->cmsColl = $cmsColl;
        $this->cmsUser = $user;

    }

    /**
     * @test 
     */
    public function shouldHaveBlankAsDefaultKeyColumn()
    {
        $this->assertEquals('', $this->coll->getKeyField());
    }	


    /**
     * @test
     */
    public function shouldUseSpecifiedKeyColumn()
    {
        $coll = new Doctrine_Collection('ForumUser', 'id');
        $this->assertEquals('id', $coll->getKeyField());
    }

    /**
     * This test is currently failing. I do not understand why it should be 
     * possible to set this to something that is not valid. 
     *
     * @test 
     * @expectedException Doctrine_Collection_Exception
     */
    public function shouldThrowExceptionIfNonValidFieldSetAsKey()
    {
        $coll = new Doctrine_Collection('ForumUser', 'xxNonValidFieldxx');
    }

    /**
     * @test 
     */
    public function shouldSerializeEmptyCollection()
    {
        $serialized = serialize($this->coll);
        $this->assertTrue(is_string($serialized));
    }

    /**
     * @test 
     */
    public function shouldUnserializeEmptyCollectionIntoObject()
    {
        $serialized = serialize($this->coll);
        $coll = unserialize($serialized);
        $this->assertEquals('Doctrine_Collection', get_class($coll));
    }

    /**
     * @test 
     */
    /*public function shouldSetKeyColumnWhenAddingNewRowAsArray()
    {
        $this->assertTrue(isset($this->cmsColl['test']));
        $this->assertEquals($this->cmsUser,  $this->cmsColl['test']);
    }*/


    /**
     * @test
     */
    /*public function shouldSerializeAndUnserializeCollectionWithData()
    {
        $serialized = serialize($this->cmsColl);
        $coll = unserialize($serialized);

        $this->assertEquals('username', $coll->getKeyField());
        $this->assertTrue(isset($coll['test']));
        $user = $coll['test'];
        $this->assertTrue($user instanceOf CmsUser);
        $this->assertEquals('test', $user['username']);
    }*/

}
