<?php ?>
The WHERE clause, if given, indicates the condition or conditions that the records must satisfy to be selected.

Doctrine_Query provides easy to use WHERE -part management methods where and addWhere. The where methods always overrides
the query WHERE -part whereas addWhere adds new condition to the WHERE -part stack.


<code type="php">
// find all groups where the group primary key is bigger than 10

\$coll = \$q->from('Group')->where('Group.id > 10');

// the same query using Doctrine_Expression component
\$e    = \$q->expr;
\$coll = \$q->from('Group')->where(\$e->gt('Group.id', 10));
?></code>



Using regular expression operator: 


<code type="php">
// find all users where users where user name matches
// a regular expression, regular expressions must be 
// supported by the underlying database

\$coll = \$conn->query(\"FROM User WHERE User.name REGEXP '[ad]'\</code></code>              



DQL has support for portable LIKE operator:   


<code type="php">
// find all users and their associated emails 
// where SOME of the users phonenumbers
// (the association between user and phonenumber 
// tables is One-To-Many) starts with 123

\$coll = \$q->select('u.*, e.*')
            ->from('User u LEFT JOIN u.Email e LEFT JOIN u.Phonenumber p')
            ->where(\"p.phonenumber LIKE '123%'\</code></code>
 


Using multiple conditions and condition nesting are also possible:  


<code type="php">
// multiple conditions

\$coll = \$q->select('u.*')
            ->from('User u LEFT JOIN u.Email e')
            ->where(\"u.name LIKE '%Jack%' AND e.address LIKE '%@drinkmore.info'\");

// nesting conditions

\$coll = \$q->select('u.*')
            ->from('User u LEFT JOIN u.Email e')
            ->where(\"u.name LIKE '%Jack%' OR u.name LIKE '%John%') AND e.address LIKE '%@drinkmore.info'\</code></code>


