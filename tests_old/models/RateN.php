<?php
class RateN extends Doctrine_Entity{
  
  public static function initMetadata($class) {
    $class->setTableName('rates');
    $class->setColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $class->setColumn('policy_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $class->setColumn('coverage_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $class->setColumn('liability_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $class->setColumn('total_rate', 'float', null, array (  'notnull' => true,  'notblank' => true,));
    $class->hasOne('PolicyCodeN', array('local' => 'policy_code', 'foreign' => 'code' ));
    $class->hasOne('CoverageCodeN', array('local' => 'coverage_code', 'foreign' => 'code' ));
    $class->hasOne('LiabilityCodeN', array('local' => 'liability_code', 'foreign' => 'code' ));
  }
  
}
