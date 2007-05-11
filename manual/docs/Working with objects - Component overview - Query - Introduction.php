
DQL (Doctrine Query Language) is a object query language which allows
you to find objects. DQL understands things like object relationships, polymorphism and
inheritance (including column aggregation inheritance).
For more info about DQL see the actual DQL chapter.



Doctrine_Query along with Doctrine_Expression provide an easy-to-use wrapper for writing DQL queries. Creating a new
query object can be done by either using the new operator or by calling create method. The create method exists for allowing easy
method call chaining.

<code type="php">
// initalizing a new Doctrine_Query (using the current connection)
$q = new Doctrine_Query();

// initalizing a new Doctrine_Query (using custom connection parameter)
// here $conn is an instance of Doctrine_Connection
$q = new Doctrine_Query($conn);

// an example using the create method
// here we simple fetch all users
$users = new Doctrine_Query::create()->from('User')->execute();
</code>
