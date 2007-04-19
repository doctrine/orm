
The FROM clause indicates the component or components from which to retrieve records.
If you name more than one component, you are performing a join.
For each table specified, you can optionally specify an alias. Doctrine_Query offers easy to use
methods such as from(), addFrom(), leftJoin() and innerJoin() for managing the FROM part of your DQL query.



<code type="php">
// find all users
\$q = new Doctrine_Query();

\$coll = \$q->from('User')->execute();

// find all users with only their names (and primary keys) fetched

\$coll = \$q->select('u.name')->('User u');
?></code>     


The following example shows how to use leftJoin and innerJoin methods:  



<code type="php">
// find all groups

$coll = $q->from("FROM Group");

// find all users and user emails

$coll = $q->from("FROM User u LEFT JOIN u.Email e");

// find all users and user emails with only user name and
// age + email address loaded

$coll = $q->select('u.name, u.age, e.address')
          ->from('FROM User u')
          ->leftJoin('u.Email e')
          ->execute();

// find all users, user email and user phonenumbers

$coll = $q->from('FROM User u')
          ->innerJoin('u.Email e')
          ->innerJoin('u.Phonenumber p')
          ->execute();
</code>
