<?php

// using PDO dsn for connecting sqlite memory table

$dbh = Doctrine_DB::getConnection('sqlite::memory:');

class Counter extends Doctrine_DB_EventListener {
    private $queries = 0;

    public function onQuery(Doctrine_DB $dbh, $query, $params) {
        $this->queries++;
    }
    public function count() {
        return count($this->queries);
    }
}
class OutputLogger extends Doctrine_Overloadable {
    public function __call($m, $a) {
        print $m." called!";
    }
}
$counter = new Counter();

$dbh->addListener($counter);
$dbh->addListener(new OutputLogger());

$dbh->query("SELECT * FROM foo"); 
// prints:
// onPreQuery called!
// onQuery called!

print $counter->count(); // 1

?>
