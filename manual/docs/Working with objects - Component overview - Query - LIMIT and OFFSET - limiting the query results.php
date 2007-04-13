
<code type="php">

// find the first ten users and associated emails

$q = new Doctrine_Query();

$coll = $q->from('User u LEFT JOIN u.Email e')->limit(10);

// find the first ten users starting from the user number 5

$coll = $q->from('User u')->limit(10)->offset(5);

</code>
