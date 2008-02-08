<?php
class ORM_TestItem extends Doctrine_Record {
   public function setTableDefinition() {
        $this->setTableName('test_items');
        $this->hasColumn('id', 'integer', 11, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 255); 
   } 

   public function setUp() {

        $this->hasOne('ORM_TestEntry', 'ORM_TestEntry.itemID'); 
   } 
}
