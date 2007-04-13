<?php ?>
Doctrine_Db has a pluggable event listener architecture. It provides before and after
listeners for all relevant methods. Every listener method takes one parameter: a Doctrine_Db_Event object, which
holds info about the occurred event.



Every listener object must either implement the Doctrine_Db_EventListener_Interface or Doctrine_Overloadable interface. 
Using Doctrine_Overloadable interface
only requires you to implement __call() which is then used for listening all the events.  


<?php

$str = "<?php
class OutputLogger extends Doctrine_Overloadable {
    public function __call(\$m, \$a) {
        print \$m . ' called!';
    }
}
?>";
renderCode($str);
?>



For convience
you may want to make your listener class extend Doctrine_Db_EventListener which has empty listener methods, hence allowing you not to define
all the listener methods by hand. The following listener, 'MyLogger', is used for listening only onPreQuery and onQuery methods.


<?php
$str = "<?php
class MyLogger extends Doctrine_Db_EventListener {
    public function onPreQuery(Doctrine_Db_Event \$event) {
        print 'database is going to be queried!';
    }
    public function onQuery(Doctrine_Db_Event \$event) {
        print 'executed: ' . \$event->getQuery();
    }
}
?>";
renderCode($str);
?>



Now the next thing we need to do is bind the eventlistener objects to our database handler.




<code type="php">

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
</code>
