<?php
class Doctrine_Export_Sqlite_TestCase extends Doctrine_UnitTestCase {
    public function testCreateDatabaseDoesNotExecuteSql() {
        try {
            $this->export->createDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testDropDatabaseDoesNotExecuteSql() {
        try {
            $this->export->dropDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableSupportsAutoincPks() {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true));

        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id INTEGER UNSIGNED PRIMARY KEY AUTOINCREMENT)');
    }
    public function testCreateTableSupportsDefaultAttribute() {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INTEGER DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks() {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INTEGER, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsIndexes()
    {
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true),
                         'name' => array('type' => 'string', 'length' => 4),
                         );

        $options = array('primary' => array('id'),
                         'indexes' => array('myindex' => array('fields' => array('id', 'name')))
                         );

        $this->export->createTable('sometable', $fields, $options);
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE sometable (id INTEGER UNSIGNED PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id, name))');
    }
    public function testUnknownIndexSortingAttributeThrowsException()
    {
        $fields = array('id' => array('sorting' => 'ASC'),
                        'name' => array('sorting' => 'unknown'));

        try {
            $this->export->getIndexFieldDeclarationList($fields);
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableSupportsIndexesWithCustomSorting()
    {
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true),
                         'name' => array('type' => 'string', 'length' => 4),
                         );

        $options = array('primary' => array('id'),
                         'indexes' => array('myindex' => array(
                                                    'fields' => array(
                                                            'id' => array('sorting' => 'ASC'), 
                                                            'name' => array('sorting' => 'DESC')
                                                                )
                                                            ))
                         );

        $this->export->createTable('sometable', $fields, $options);
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE sometable (id INTEGER UNSIGNED PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id ASC, name DESC))');
    }
    public function testExportSupportsForeignKeys()
    {
        $r = new ForeignKeyTest;
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE foreign_key_test (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(2147483647), code INTEGER, content VARCHAR(4000), parent_id INTEGER, FOREIGN KEY parent_id REFERENCES foreign_key_test(id), FOREIGN KEY id REFERENCES foreign_key_test(parent_id) ON UPDATE RESTRICT ON DELETE CASCADE)');
    }
}
?>
