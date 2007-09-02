<?php
class ORM_TestEntry extends Doctrine_Record {
   public function setTableDefinition() {
        $this->setTableName('test_entries');
        $this->hasColumn('id', 'integer', 11, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 255); 
        $this->hasColumn('stamp', 'timestamp');
        $this->hasColumn('amount', 'float'); 
        $this->hasColumn('itemID', 'integer'); 
   } 
    
   public function setUp() {  
        $this->hasOne('ORM_TestItem', 'ORM_TestEntry.itemID'); 
   }
}
