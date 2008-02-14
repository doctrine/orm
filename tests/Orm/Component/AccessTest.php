<?php
require_once 'lib/DoctrineTestInit.php';

class Orm_Component_AccessTest extends Doctrine_OrmTestCase
{

    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->user = new ForumUser();
    }

    /**
     * @test 
     */
    public function shouldMarkExistingFieldAsSetOnNewRecord()
    {
        $this->assertTrue(isset($this->user->username));
        $this->assertTrue(isset($this->user['username']));
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
        $this->user->username ='meus';
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
     */
    public function shouldSetArrayOfValusInRecord()
    {
        $this->user->setArray(array(
            'username' => 'meus',
            'id'       => 22));

        $this->assertEquals('meus', $this->user->username);
        $this->assertEquals('meus', $this->user['username']);

        $this->assertEquals(22, $this->user->id);
        $this->assertEquals(22, $this->user['id']);
    }


    /**
     * @test 
     * @expectedException Doctrine_Record_Exception
     */
    public function shouldNotBeAbleToSetNonExistantField()
    {
        $this->user->rat ='meus';
    }

    /**
     * @test 
     * @expectedException Doctrine_Record_Exception
     */
    public function shouldNotBeAbleToSetNonExistantFieldWithOffset()
    {
        $this->user['rat'] ='meus';
    }

    /**
     * @test 
     * @expectedException Doctrine_Record_Exception
     */
    public function shouldNotBeAbleToSetNonExistantFieldAsPartInSetArray()
    {
        $this->user->setArray(array(
            'rat' => 'meus',
            'id'       => 22));

    }


    /**
     * @test 
     */
    public function newCollectionShouldBeEmpty()
    {
        $col = new Doctrine_Collection('ForumUser');
        $this->assertEquals(0, count($col));
        $this->assertFalse(isset($coll[0]));
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
     *  
     * @test
     * @expectedException Doctrine_Exception
     */
    public function shouldNotBeAbleToSetNullFieldInRecord()
    {
        $this->user->offsetSet(null, 'test');

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

class AccessStub extends Doctrine_Access {}
