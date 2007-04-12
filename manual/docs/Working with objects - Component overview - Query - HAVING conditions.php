<?php ?>
Doctrine_Query provides having() method for adding HAVING conditions to the DQL query. This method is identical in function to the Doctrine_Query::where() method.
<br \><br \>
If you call having() multiple times, the conditions are ANDed together; if you want to OR a condition, use orHaving().   <br \><br \>
<?php
renderCode("<?php
\$q = new Doctrine_Query();

\$users = \$q->select('u.name')
             ->from('User u')
             ->leftJoin('u.Phonenumber p');
             ->having('COUNT(p.id) > 3');
?>");
?>

