<?php ?>
The FROM clause indicates the component or components from which to retrieve records.
If you name more than one component, you are performing a join.
For each table specified, you can optionally specify an alias. Doctrine_Query offers easy to use
methods such as from(), addFrom(), leftJoin() and innerJoin() for managing the FROM part of your DQL query.
<br \><br \>
<?php
renderCode("<?php
// find all users
\$q = new Doctrine_Query();

\$coll = \$q->from('User')->execute();

// find all users with only their names (and primary keys) fetched

\$coll = \$q->select('u.name')->('User u');
?>");
?>     <br \><br \>
The following example shows how to use leftJoin and innerJoin methods:  <br \><br \>
