Doctrine provides two relation operators: '.' aka dot and ':' aka colon.



The dot-operator is used for SQL LEFT JOINs and the colon-operator is used
for SQL INNER JOINs. Basically you should use dot operator if you want for example
to select all users and their phonenumbers AND it doesn't matter if the users actually have any phonenumbers.



On the other hand if you want to select only the users which actually have phonenumbers you should use the colon-operator.

<code type="php">
$query->from('User u')->innerJoin('u.Email e');

$query->execute();

// executed SQL query:
// SELECT ... FROM user INNER JOIN email ON ...

$query->from('User u')->leftJoin('u.Email e');

$query->execute();

// executed SQL query:
// SELECT ... FROM user LEFT JOIN email ON ...
</code>
