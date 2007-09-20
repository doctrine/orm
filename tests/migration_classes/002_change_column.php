<?php
class ChangeColumn extends Doctrine_Migration
{
    public function up()
    {
        $this->changeColumn('migration_test', 'field1', 'integer');
    }
    
    public function down()
    {
        $this->changeColumn('migration_test', 'field1', 'string');
    }  
}