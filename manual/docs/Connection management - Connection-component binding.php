<?php ?>
Doctrine allows you to bind connections to components (= your ActiveRecord classes). This means everytime a component issues a query
or data is being fetched from the table the component is pointing at Doctrine will use the bound connection.

 

<code type="php">
\$conn = \$manager->openConnection(new PDO('dsn','username','password'), 'connection 1');

\$conn2 = \$manager->openConnection(new PDO('dsn2','username2','password2'), 'connection 2');

\$manager->bindComponent('User', 'connection 1');

\$manager->bindComponent('Group', 'connection 2');

\$q = new Doctrine_Query();

// Doctrine uses 'connection 1' for fetching here
\$users = \$q->from('User u')->where('u.id IN (1,2,3)')->execute();

// Doctrine uses 'connection 2' for fetching here
\$groups = \$q->from('Group g')->where('g.id IN (1,2,3)')->execute();
?></code>

