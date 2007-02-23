<?php ?>
Doctrine_Db has a pluggable event listener architecture. It provides before and after
listeners for all relevant methods. Every listener method takes one parameter: a Doctrine_Db_Event object, which
holds info about the occurred event.
<br \><br \>
Every listener object must either implement the Doctrine_Db_EventListener_Interface or Doctrine_Overloadable interface. 
Using Doctrine_Overloadable interface
only requires you to implement __call() which is then used for listening all the events.  <br \><br \>
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
<br \><br \>
For convience
you may want to make your listener class extend Doctrine_Db_EventListener which has empty listener methods, hence allowing you not to define
all the listener methods by hand. The following listener, 'MyLogger', is used for listening only onPreQuery and onQuery methods.<br \><br \>
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
<br \><br \>
Now the next thing we need to do is bind the eventlistener objects to our database handler.
<br \><br \>
