<?php
class ValidatorTest_FootballPlayer extends Doctrine_Entity {
   public static function initMetadata($class) {
      $class->setColumn('person_id', 'string', 255);     
      $class->setColumn('team_name', 'string', 255);
      $class->setColumn('goals_count', 'integer', 4);
   }
}
