<?php
require_once("../draft/DB.php");

class Doctrine_DB_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { }
    public function init() { }
    
    public function testInvalidDSN() {
        try {
            $conn = Doctrine_DB2::getConnection('');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
        try {
            $conn = Doctrine_DB2::getConnection('unknown');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }   
        try {
            $conn = Doctrine_DB2::getConnection(0);
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidScheme() {
        try {
            $conn = Doctrine_DB2::getConnection('unknown://:memory:');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidHost() {
        try {
            $conn = Doctrine_DB2::getConnection('mysql://user:password@');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDatabase() {
        try {
            $conn = Doctrine_DB2::getConnection('mysql://user:password@host/');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }

    public function testGetConnection() {
        $conn = Doctrine_DB2::getConnection('mysql://zYne:password@localhost/test');
        $this->assertEqual($conn->getDSN(), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($conn->getUsername(), 'zYne');
        $this->assertEqual($conn->getPassword(), 'password');
        


        $conn = Doctrine_DB2::getConnection('sqlite://:memory:');
        
        $this->assertEqual($conn->getDSN(), 'sqlite::memory:');
        $this->assertEqual($conn->getUsername(), null);
        $this->assertEqual($conn->getPassword(), null);
    }
    
}
?>
