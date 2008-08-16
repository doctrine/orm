<?php
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
 * Testcase for basic accessor/mutator functionality.
 *
 * @package     Doctrine
 * @author      Bjarte Stien Karlsen <doctrine@bjartek.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision: 3754 $
 */
require_once 'lib/DoctrineTestInit.php';

class AccessStub extends Doctrine_Access {}

class Orm_Component_AccessTest extends Doctrine_OrmTestCase
{

    private $user;

    public function setUp()
    {
        parent::setUp();
        $em = new Doctrine_EntityManager(new Doctrine_Connection_Mock());
        $this->user = new ForumUser();
    }

    /*public function testAccessorOverridePerformance() {
        $this->user->username;
        $start = microtime(true);
        for ($i = 0; $i < 1; $i++) {
            $this->user->username;
        }
        $end = microtime(true);
        echo ($end - $start) . " seconds" . PHP_EOL;
    }*/

    /**
     * @test 
     */
    public function shouldMarkEmptyFieldAsNotSetOnNewRecord()
    {
        $this->assertFalse(isset($this->user->username));
        $this->assertFalse(isset($this->user['username']));
    }

    /**
     * @test 
     */
    public function shouldMarkNonExistantFieldAsNotSetOnNewRecord()
    {
        $this->assertFalse(isset($this->user->rat));
        $this->assertFalse(isset($this->user['rat']));
    }

    /**
     * @test  
     */
    public function shouldSetSingleValueInRecord()
    {
        $this->user->username = 'meus';
        $this->assertEquals('meus', $this->user->username);
        $this->assertEquals('meus', $this->user['username']);
    }


    /**
     * @test  
     */
    public function shouldSetSingleValueInRecordWithOffset()
    {
        $this->user['username'] ='meus';
        $this->assertEquals('meus', $this->user->username);
        $this->assertEquals('meus', $this->user['username']);
    }


    /**
     * @test 
     * @expectedException Doctrine_Entity_Exception
     */
    public function shouldNotBeAbleToSetNonExistantField()
    {
        $this->user->rat = 'meus';
    }

    /**
     * @test 
     * @expectedException Doctrine_Entity_Exception
     */
    public function shouldNotBeAbleToSetNonExistantFieldWithOffset()
    {
        $this->user['rat'] = 'meus';
    }


    /**
     * @test 
     */
    public function newCollectionShouldBeEmpty()
    {
        $col = new Doctrine_Collection('ForumUser');
        $this->assertEquals(0, count($col));
        $this->assertFalse(isset($coll[0]));
    }

    /**
     * @test 
     */
    public function shouldBeAbleToUnsetWithOffsetFromCollection()
    {
        $col = new Doctrine_Collection('ForumUser');
        $col[0] = new ForumUser();
        $this->assertTrue(isset($col[0]));
        unset($col[0]);
        $this->assertFalse(isset($col[0]));
    }
    /**
     * @test 
     */

    public function shouldBeAbleToUnsetFromCollection()
    {
        $col = new Doctrine_Collection('ForumUser');
        $col->test = new ForumUser();
        $this->assertTrue(isset($col->test));
        unset($col->test);
        $this->assertFalse(isset($col->test));
    }

    /**
     * @test
     * @expectedException Doctrine_Exception
     */
     public function shouldNotBeAbleToUseContainsWhenNotImplemented()
     {
         $stub = new AccessStub();
         isset($stub['foo']);
     }

    /**
     * @test
     * @expectedException Doctrine_Exception
     */
     public function shouldNotBeAbleToUseSetWhenNotImplemented()
     {
         $stub = new AccessStub();
         $stub['foo']  = 'foo';
     }

    /**
     * @test
     * @expectedException Doctrine_Exception
     */
     public function shouldNotBeAbleToUseUnsetWhenNotImplemented()
     {
         $stub = new AccessStub();
         unset($stub['foo']);
     }

    /**
     * @test
     * @expectedException Doctrine_Exception
     */
     public function shouldNotBeAbleToUseGetWhenNotImplemented()
     {
         $stub = new AccessStub();
         $stub['foo'];
     }
}
