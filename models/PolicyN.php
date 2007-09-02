<?php
class PolicyN extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('policies');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('rate_id', 'integer', 4, array ( ));
    $this->hasColumn('policy_number', 'integer', 4, array (  'unique' => true, ));
  }
  
  public function setUp(){
    $this->hasOne('RateN', array('local' => 'rate_id', 'foreign' => 'id' ));
  }

}
