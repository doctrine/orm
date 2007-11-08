<?php
class MysqlChangeColumn extends Doctrine_Migration
{
    public function up()
    {
        $this->renameColumn('migration_test','field2','field3');
    }
    
    public function down()
    {
    		$this->renameColumn('migration_test','field3','field2');
    }  
}
