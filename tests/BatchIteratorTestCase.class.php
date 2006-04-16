<?php
require_once("UnitTestCase.class.php");
class Doctrine_BatchIteratorTestCase extends Doctrine_UnitTestCase {
    public function testIterator() {
        $graph = new Doctrine_DQL_Parser($this->session);
        $entities = $graph->query("FROM Entity");
        $i = 0;
        foreach($entities as $entity) {
            $this->assertEqual(gettype($entity->name),"string");
            $i++;
        }
        $this->assertTrue($i == $entities->count());
        
        $user = $graph->query("FROM User");
        foreach($user[1]->Group as $group) {
            print $group->name;
        }                                   	
    }

}
?>
