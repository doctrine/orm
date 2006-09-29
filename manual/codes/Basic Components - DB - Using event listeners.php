<?php

// using PDO dsn for connecting sqlite memory table

$dbh = Doctrine_DB::getConnection('sqlite::memory:');

class MyLogger extends Doctrine_DB_EventListener {
    public function onPreQuery(Doctrine_DB $dbh, $query, $params) {
        print "database is going to be queried!";
    }
    public function onQuery(Doctrine_DB $dbh, $query, $params) {
        print "executed: $query";
    }
}

$dbh->setListener(new MyLogger());

$dbh->query("SELECT * FROM foo"); 
// prints:
// database is going to be queried
// executed: SELECT * FROM foo


class MyLogger2 extends Doctrine_Overloadable {
    public function __call($m, $a) {
        print $m." called!";
    }
}

$dbh->setListener(new MyLogger2());

$dbh->exec("DELETE FROM foo");
// prints:
// onPreExec called!
// onExec called!
?>
