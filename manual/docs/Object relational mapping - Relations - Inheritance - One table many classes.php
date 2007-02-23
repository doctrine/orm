When it comes to handling inheritance Doctrine is very smart.
In the following example we have one database table called 'entity'.
Users and groups are both entities and they share the same database table.
The only thing we have to make is 3 records (Entity, Group and User).
<br /><br />
Doctrine is smart enough to know that the inheritance type here is one-table-many-classes.

