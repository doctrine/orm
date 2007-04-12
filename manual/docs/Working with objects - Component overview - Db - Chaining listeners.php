Doctrine_Db supports event listener chaining. It means multiple listeners can be attached for 
listening the events of a single instance of Doctrine_Db. 
<br \><br \>
For example you might want to add different aspects to your Doctrine_Db instance on-demand. These aspects may include
caching, query profiling etc.
<br \><br \>


<code type="php">

// using PDO dsn for connecting sqlite memory table

$dbh = Doctrine_Db::getConnection('sqlite::memory:');

class Counter extends Doctrine_Db_EventListener {
    private $queries = 0;

    public function onQuery(Doctrine_Db_Event $event) {
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

</code>
