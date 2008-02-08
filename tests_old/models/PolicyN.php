<?php
class PolicyN extends Doctrine_Record {
  
  public static function initMetadata($class) {
    $class->setTableName('policies');
    $class->setColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $class->setColumn('rate_id', 'integer', 4, array ( ));
    $class->setColumn('policy_number', 'integer', 4, array (  'unique' => true, ));
    $class->hasOne('RateN', array('local' => 'rate_id', 'foreign' => 'id' ));
  }

}
