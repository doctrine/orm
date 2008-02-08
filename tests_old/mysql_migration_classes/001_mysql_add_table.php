<?php
class MysqlAddTable extends Doctrine_Migration
{
    public function up()
    {
        $this->createTable('migration_test', array('field1' => array('type' => 'string')));
        $this->addColumn('migration_test', 'field2', 'integer');
    }
    
    public function down()
    {
        $this->dropTable('migration_test');
    }
}