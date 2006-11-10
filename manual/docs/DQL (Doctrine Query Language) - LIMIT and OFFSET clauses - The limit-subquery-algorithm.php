The limit-subquery-algorithm is an algorithm that DQL parser uses internally when one-to-many / many-to-many relational 
data is being fetched simultaneously. This kind of special algorithm is needed for the LIMIT clause to limit the number 
of records instead of sql result set rows. 
<br \><br \>
In the following example we have users and phonenumbers with their relation being one-to-many. Now lets say we want fetch the first 20 users
and all their related phonenumbers.
<br \><br \>
Now one might consider that adding a simple driver specific LIMIT 20 at the end of query would return the correct results.
Thats wrong, since we you might get anything between 1-20 users as the first user might have 20 phonenumbers and then record set would consist of 20 rows.
<br \><br \>
DQL overcomes this problem with subqueries and with complex but efficient subquery analysis. In the next example we are going to fetch first 20 users and all their phonenumbers with single efficient query. 
Notice how the DQL parser is smart enough to use column aggregation inheritance even in the subquery and how its smart enough to use different aliases
for the tables in the subquery to avoid alias collisions.

<br \><br \>
DQL QUERY:
<div class='sql'><pre>
SELECT u.id, u.name, p.* FROM User u LEFT JOIN u.Phonenumber p LIMIT 20
</pre></div>

SQL QUERY: 
<div class='sql'>
<pre>
SELECT
    e.id AS e__id,
    e.name AS e__name,
    p.id AS p__id,
    p.phonenumber AS p__phonenumber,
    p.entity_id AS p__entity_id
FROM entity e
LEFT JOIN phonenumber p ON e.id = p.entity_id
WHERE e.id IN (
SELECT DISTINCT e2.id
FROM entity e2
WHERE (e2.type = 0) LIMIT 20) AND (e.type = 0)
</pre>
</div>

<br \><br \>
In the next example we are going to fetch first 20 users and all their phonenumbers and only 
those users that actually have phonenumbers with single efficient query, hence we use an INNER JOIN. 
Notice how the DQL parser is smart enough to use the INNER JOIN in the subquery.

<br \><br \>
DQL QUERY:
<div class='sql'><pre>
SELECT u.id, u.name, p.* FROM User u LEFT JOIN u.Phonenumber p LIMIT 20
</pre></div>

SQL QUERY: 
<div class='sql'>
<pre>
SELECT 
    e.id AS e__id,
    e.name AS e__name,
    p.id AS p__id,
    p.phonenumber AS p__phonenumber,
    p.entity_id AS p__entity_id
FROM entity e
LEFT JOIN phonenumber p ON e.id = p.entity_id
WHERE e.id IN (
SELECT DISTINCT e2.id
FROM entity e2
INNER JOIN phonenumber p2 ON e2.id = p2.entity_id
WHERE (e2.type = 0) LIMIT 20) AND (e.type = 0)
</pre>
</div>

