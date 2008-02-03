<?php
class ValidatorTest_Person extends Doctrine_Record {
   public static function initMetadata($class) {
      $class->setColumn('identifier', 'integer', 4, array('notblank', 'unique'));
      $class->setColumn('is_football_player', 'boolean');
      $class->hasOne('ValidatorTest_FootballPlayer', array('local' => 'id', 'foreign' => 'person_id'));
   }
}
