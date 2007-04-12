A subquery can contain any of the keywords or clauses that an ordinary SELECT query can contain.
<br \><br \>
Some advantages of the subqueries:
<ul>
<li \>They allow queries that are structured so that it is possible to isolate each part of a statement.

<li \>They provide alternative ways to perform operations that would otherwise require complex joins and unions.

<li \>They are, in many people's opinion, readable. Indeed, it was the innovation of subqueries that gave people the original idea of calling the early SQL “Structured Query Language.”

</ul>

<code type="php">
// finding all users which don't belong to any group 1
$query = "FROM User WHERE User.id NOT IN 
                        (SELECT u.id FROM User u 
                         INNER JOIN u.Group g WHERE g.id = ?";
                         
$users = $conn->query($query, array(1));

// finding all users which don't belong to any groups
// Notice: 
// the usage of INNER JOIN
// the usage of empty brackets preceding the Group component

$query = "FROM User WHERE User.id NOT IN 
                        (SELECT u.id FROM User u 
                         INNER JOIN u.Group g)";

$users = $conn->query($query);
</code>
