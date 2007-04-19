
*  GROUP BY and HAVING clauses can be used for dealing with aggregate functions


*  Following aggregate functions are availible on DQL: COUNT, MAX, MIN, AVG, SUM


Selecting alphabetically first user by name.
<code>
SELECT MIN(u.name) FROM User u
</code>

Selecting the sum of all Account amounts.
<code>
SELECT SUM(a.amount) FROM Account a
</code>



*  Using an aggregate function in a statement containing no GROUP BY clause, results in grouping on all rows. In the example above 
we fetch all users and the number of phonenumbers they have.

<code>
SELECT u.*, COUNT(p.id) FROM User u, u.Phonenumber p GROUP BY u.id
</code>



*  The HAVING clause can be used for narrowing the results using aggregate values. In the following example we fetch
all users which have atleast 2 phonenumbers
<code>
SELECT u.* FROM User u, u.Phonenumber p HAVING COUNT(p.id) >= 2
</code>




<code type="php">

// retrieve all users and the phonenumber count for each user

$users = $conn->query("SELECT u.*, COUNT(p.id) count FROM User u, u.Phonenumber p GROUP BY u.id");

foreach($users as $user) {
    print $user->name . ' has ' . $user->Phonenumber[0]->count . ' phonenumbers';
}
</code>
