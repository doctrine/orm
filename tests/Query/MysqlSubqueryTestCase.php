<?php
class Doctrine_Query_MysqlSubquery_TestCase extends Doctrine_UnitTestCase 
{
    public function testInit()
    {
        $this->dbh = new Doctrine_Adapter_Mock('mysql');
        $this->conn = Doctrine_Manager::getInstance()->openConnection($this->dbh);
    }
    
    public function testGetLimitSubqueryOrderBy()
    {
        $q = new Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums');
        $q->groupby('u.id');
        // this causes getLimitSubquery() to be used, and it fails
        $q->limit(5);
        
        try {
            $q->execute();
        } catch (Doctrine_Exception $e) {

            $this->fail();
        }
        
        $queries = $this->dbh->getAll();
        
        $this->assertEqual($queries[0], 'SELECT DISTINCT e2.id, COUNT(DISTINCT a.id) AS a__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a__0 LIMIT 5');

    }
}