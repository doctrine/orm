<?php
require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_EntityManagerMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';
require_once 'lib/mocks/Doctrine_ClassMetadataMock.php';

/**
 * UnitOfWork tests.
 * These tests run without a database through mocking the
 * persister/connection/sequence used by the UnitOfWork.
 */
class Orm_UnitOfWorkTest extends Doctrine_OrmTestCase
{
    private $_unitOfWork;
    private $_user;
    
    // Mocks
    
    // Provides a sequence mock to the UnitOfWork
    private $_connectionMock;
    // The sequence mock
    private $_idGeneratorMock;
    // The persister mock used by the UnitOfWork
    private $_persisterMock;
    // The EntityManager mock that provides the mock persister
    private $_emMock;
    private $_platformMock;
    private $_classMetadataMock;
    
    protected function setUp() {
        parent::setUp();

        $this->_connectionMock = new Doctrine_ConnectionMock(array());
        $this->_platformMock = new Doctrine_DatabasePlatformMock();
        $this->_platformMock->setPrefersIdentityColumns(true);
        $this->_emMock = Doctrine_EntityManagerMock::create($this->_connectionMock, "uowMockEm");
        $this->_idGeneratorMock = new Doctrine_SequenceMock($this->_emMock);
        $this->_connectionMock->setDatabasePlatform($this->_platformMock);
        
        $this->_classMetadataMock = new Doctrine_ClassMetadataMock("ForumUser", $this->_emMock);
        $this->_classMetadataMock->setIdGenerator($this->_idGeneratorMock);
        
        $this->_persisterMock = new Doctrine_EntityPersisterMock(
                $this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
        $this->_emMock->setEntityPersister($this->_persisterMock);
        
        $this->_emMock->activate();
        
        // SUT
        $this->_unitOfWork = $this->_emMock->getUnitOfWork();
        
        $this->_user = new ForumUser();
        $this->_user->id = 1;
        $this->_user->username = 'romanb';
    }
    
    protected function tearDown() {
        $this->_user->free();
    }
    
    /* Basic registration tests */
    
    public function testRegisterNew()
    {
        // registerNew() is normally called in save()/persist()
        $this->_unitOfWork->registerNew($this->_user);
        $this->assertTrue($this->_unitOfWork->isRegisteredNew($this->_user));
        $this->assertTrue($this->_unitOfWork->isInIdentityMap($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredDirty($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));
    }
    
    /*public function testRegisterNewPerf() {
        $s = microtime(true);

        for ($i=1; $i<40000; $i++) {
            $user = new ForumUser();
            $user->id = $i;
            $this->_unitOfWork->registerNew($user);
        }
        $e = microtime(true);
        
        echo $e - $s . " seconds" . PHP_EOL;
    }*/
    
    public function testRegisterDirty()
    {
        $this->assertEquals(Doctrine_Entity::STATE_NEW, $this->_user->_state());
        $this->assertFalse($this->_unitOfWork->isInIdentityMap($this->_user));
        $this->_unitOfWork->registerDirty($this->_user);
        $this->assertTrue($this->_unitOfWork->isRegisteredDirty($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredNew($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));
    }
    
    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));
        $this->_unitOfWork->registerDeleted($this->_user);
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));        
    }
    
    
    /* Operational tests */
    
    public function testSavingSingleEntityWithIdentityColumnForcesInsert()
    {
        $this->assertEquals(Doctrine_Entity::STATE_NEW, $this->_user->_state());
        
        $this->_unitOfWork->save($this->_user);
        
        $this->assertEquals(1, count($this->_persisterMock->getInserts())); // insert forced
        $this->assertEquals(0, count($this->_persisterMock->getUpdates()));
        $this->assertEquals(0, count($this->_persisterMock->getDeletes()));
        
        $this->assertTrue($this->_unitOfWork->isInIdentityMap($this->_user));
        $this->assertEquals(Doctrine_Entity::STATE_MANAGED, $this->_user->_state());
        
        // should no longer be scheduled for insert
        $this->assertFalse($this->_unitOfWork->isRegisteredNew($this->_user));        
        // should have an id
        $this->assertTrue(is_numeric($this->_user->id));
        
        // Now lets check whether a subsequent commit() does anything
        
        $this->_persisterMock->reset();
        
        $this->_unitOfWork->commit(); // shouldnt do anything
        
        // verify that nothing happened
        $this->assertEquals(0, count($this->_persisterMock->getInserts()));
        $this->assertEquals(0, count($this->_persisterMock->getUpdates()));
        $this->assertEquals(0, count($this->_persisterMock->getDeletes()));
    }
    
    public function testCommitOrder()
    {
        $avatar = new ForumAvatar();
        $this->_user->avatar = $avatar;
        $this->_unitOfWork->save($this->_user); // save cascaded to avatar
        
        $this->assertEquals(2, count($this->_persisterMock->getInserts())); // insert forced
        $this->assertEquals(0, count($this->_persisterMock->getUpdates()));
        $this->assertEquals(0, count($this->_persisterMock->getDeletes()));
        // verify order of inserts()s
        $inserts = $this->_persisterMock->getInserts();
        $this->assertSame($avatar, $inserts[0]);
        $this->assertSame($this->_user, $inserts[1]);
        
        //...
    }
    
    public function testSavingSingleEntityWithSequenceIdGeneratorSchedulesInsert()
    {
        //...
    }
    
    public function testSavingSingleEntityWithTableIdGeneratorSchedulesInsert()
    {
        //...
    }
    
    public function testSavingSingleEntityWithSingleNaturalIdForcesInsert()
    {
        //...
    }
    
    public function testSavingSingleEntityWithCompositeIdForcesInsert()
    {
        //...
    }
    
    public function testSavingEntityGraphWithIdentityColumnsForcesInserts()
    {
        //...
    }
    
    public function testSavingEntityGraphWithSequencesDelaysInserts()
    {
        //...
    }
    
    public function testSavingEntityGraphWithNaturalIdsForcesInserts()
    {
        //...
    }
    
    public function testSavingEntityGraphWithMixedIdGenerationStrategies()
    {
        //...
    }
    
}