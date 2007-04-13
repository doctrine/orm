You can retrieve existing objects (database rows) with Doctrine_Table or Doctrine_Connection. 
Doctrine_Table provides simple methods like findBySql, findAll and find for finding objects whereas 
Doctrine_Connection provides complete OQL API for retrieving objects (see chapter 9). 

<code type="php">
$user = $table->find(3);

// access property through overloading

$name = $user->name;

// access property with get()

$name = $user->get("name");

// access property with ArrayAccess interface

$name = $user['name'];

// iterating through properties

foreach($user as $key => $value) {

}
</code>
