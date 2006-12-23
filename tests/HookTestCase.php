<?php
class Doctrine_Hook_TestCase extends Doctrine_UnitTestCase {

    public function testHookWhereAcceptsArrays() {
        $hook = new Doctrine_Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['where'] = array('u.name' => 'Jack Daniels',
                            'u.loginname' => 'TheMan');

        $hook->hookWhere($a['where']);
        $this->assertEqual($hook->getQuery()->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.name LIKE ? OR e.name LIKE ?) AND e.loginname LIKE ? AND (e.type = 0)');
    
    }
    public function testEqualParserUsesEqualOperator() {
        $parser = new Doctrine_Hook_Equal();
        
        $parser->parse('u', 'name', 'zYne');
        
        $this->assertEqual($parser->getCondition(), 'u.name = ?');
        $this->assertEqual($parser->getParams(), array('zYne'));
    }
    public function testWordLikeParserUsesLikeOperator() {
        $parser = new Doctrine_Hook_WordLike();
        
        $parser->parse('u', 'name', 'zYne');
        
        $this->assertEqual($parser->getCondition(), 'u.name LIKE ?');
        $this->assertEqual($parser->getParams(), array('zYne%'));
    }
    public function testIntegerParserSupportsIntervals() {
        $parser = new Doctrine_Hook_Integer();

        $parser->parse('m', 'year', '1998-2000');
        
        $this->assertEqual($parser->getCondition(), '(m.year > ? AND m.year < ?)');
        $this->assertEqual($parser->getParams(), array('1998', '2000'));
    }

    public function testIntegerParserSupportsEqualOperator() {
        $parser = new Doctrine_Hook_Integer();

        $parser->parse('m', 'year', '1998');

        $this->assertEqual($parser->getCondition(), 'm.year = ?');
        $this->assertEqual($parser->getParams(), array('1998'));
    }

    public function testIntegerParserSupportsNestingConditions() {
        $parser = new Doctrine_Hook_Integer();

        $parser->parse('m', 'year', '1998-2000 OR 2001');

        $this->assertEqual($parser->getCondition(), '((m.year > ? AND m.year < ?) OR m.year = ?)');
        $this->assertEqual($parser->getParams(), array('1998', '2000', '2001'));
    }
}
?>
