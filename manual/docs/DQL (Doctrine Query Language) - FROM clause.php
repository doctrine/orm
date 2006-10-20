Syntax: <br \>

<div class='sql'>
<pre>
FROM <i>component_reference</i> [[LEFT | INNER] JOIN <i>component_reference</i>] ...
</pre>
</div>

The FROM clause indicates the component or components from which to retrieve records.
If you name more than one component, you are performing a join.
For each table specified, you can optionally specify an alias.
<br \><br \>


<li \> The default join type is <i>LEFT JOIN</i>. This join can be indicated by the use of either 'LEFT JOIN' clause or simply ',', hence the following queries are equal:
<div class='sql'>
<pre>
SELECT u.*, p.* FROM User u LEFT JOIN u.Phonenumber

SELECT u.*, p.* FROM User u, u.Phonenumber p
</pre>
</div>  

<li \><i>INNER JOIN</i> produces a Cartesian product between two specified components (that is, each and every record in the first component is joined to each and every record in the second component).
So basically <i>INNER JOIN</i> can be used when you want to efficiently fetch for example all users which have one or more phonenumbers.
<div class='sql'>
<pre>
SELECT u.*, p.* FROM User u INNER JOIN u.Phonenumber p
</pre>
</div>

