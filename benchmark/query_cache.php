<?php
require_once '../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));


class Entity extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('id', 'integer', 20, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 50);
    }
}

$dbh   = new Doctrine_Db('sqlite:test.db');
$conn  = Doctrine_Manager::getInstance()->openConnection($dbh);
// initialize some entities

$coll = new Doctrine_Collection('Entity');
$i = 10;
while ($i--) {
    $coll[$i]->name = 'e ' . $i;
}
$coll->save();
$conn->clear();

$timepoint = microtime(true);

$i = 100;
$query = new Doctrine_Query();
$query->setOption('resultSetCache', new Doctrine_Cache_Array());

while ($i--) {
    $query->from('Entity e')->where('e.id > 0');
    $coll = $query->execute(array(), Doctrine::FETCH_ARRAY);
}
print 'EXECUTED 100 QUERIES WITH CACHING ENABLED + FETCH_ARRAY : ' . (microtime(true) - $timepoint) . "<br \>";

$timepoint = microtime(true);

$i = 100;

while ($i--) {
    $query = new Doctrine_Query();
    $query->from('Entity e')->where('e.id > 0');
    $coll = $query->execute(array(), Doctrine::FETCH_ARRAY);
}
print 'EXECUTED 100 QUERIES WITHOUT CACHING + FETCH_ARRAY : ' . (microtime(true) - $timepoint);

$timepoint = microtime(true);

$i = 100;
$query = new Doctrine_Query();
$query->setOption('resultSetCache', new Doctrine_Cache_Array());

while ($i--) {
    $query->from('Entity e')->where('e.id > 0');
    $coll = $query->execute();
}
print 'EXECUTED 100 QUERIES WITH CACHING ENABLED + FETCH_RECORD : ' . (microtime(true) - $timepoint) . "<br \>";

$timepoint = microtime(true);

$i = 100;

while ($i--) {
    $query = new Doctrine_Query();
    $query->from('Entity e')->where('e.id > 0');
    $coll = $query->execute();
}
print 'EXECUTED 100 QUERIES WITHOUT CACHING + FETCH_RECORD : ' . (microtime(true) - $timepoint);
