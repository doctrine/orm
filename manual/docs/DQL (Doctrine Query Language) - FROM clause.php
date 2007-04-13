Syntax: 


<code>
FROM //component_reference// [[LEFT | INNER] JOIN //component_reference//] ...
</code>

The FROM clause indicates the component or components from which to retrieve records.
If you name more than one component, you are performing a join.
For each table specified, you can optionally specify an alias.





*  The default join type is //LEFT JOIN//. This join can be indicated by the use of either 'LEFT JOIN' clause or simply ',', hence the following queries are equal:
<code>
SELECT u.*, p.* FROM User u LEFT JOIN u.Phonenumber

SELECT u.*, p.* FROM User u, u.Phonenumber p
</code>  

* //INNER JOIN// produces an intersection between two specified components (that is, each and every record in the first component is joined to each and every record in the second component).
So basically //INNER JOIN// can be used when you want to efficiently fetch for example all users which have one or more phonenumbers.
<code>
SELECT u.*, p.* FROM User u INNER JOIN u.Phonenumber p
</code>


