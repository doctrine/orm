<?php
class ValidatorTest_FootballPlayer extends Doctrine_Record {
   public function setTableDefinition() {
      $this->hasColumn('person_id', 'string', 255);     
      $this->hasColumn('team_name', 'string', 255);
      $this->hasColumn('goals_count', 'integer', 4);
   }
}
