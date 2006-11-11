<?php

// using PDO dsn for connecting sqlite memory table

$dbh = Doctrine_Db::getConnection('sqlite::memory:');

class MyLogger extends Doctrine_Db_EventListener {
    public function onPreQuery(Doctrine_Db_Event $event) {
        print "database is going to be queried!";
    }
    public function onQuery(Doctrine_Db_Event $event) {
        print "executed: " . $event->getQuery();
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
