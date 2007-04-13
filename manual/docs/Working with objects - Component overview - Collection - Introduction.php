Doctrine_Collection is a collection of records (see Doctrine_Record). As with records the collections can be deleted and saved using
Doctrine_Collection::delete() and Doctrine_Collection::save() accordingly.



When fetching data from database with either DQL API (see Doctrine_Query) or rawSql API (see Doctrine_RawSql) the methods return an instance of
Doctrine_Collection by default.



The following example shows how to initialize a new collection:

<code type="php">
$conn = Doctrine_Manager::getInstance()
    ->openConnection(new PDO("dsn", "username", "pw"));

// initalizing a new collection
$users = new Doctrine_Collection($conn->getTable('User'));

// alternative (propably easier)
$users = new Doctrine_Collection('User');

// adding some data
$coll[0]->name = 'Arnold';

$coll[1]->name = 'Somebody';

// finally save it!
$coll->save();
</code>
