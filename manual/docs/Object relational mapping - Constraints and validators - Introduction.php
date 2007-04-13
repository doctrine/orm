Data types are a way to limit the kind of data that can be stored in a table. For many applications, however, the constraint they provide is too coarse. For example, a column containing a product price should probably only accept positive values. But there is no standard data type that accepts only positive numbers. Another issue is that you might want to constrain column data with respect to other columns or rows. For example, in a table containing product information, there should be only one row for each product number.
[source: http://www.postgresql.org/docs/8.2/static/ddl-constraints.html#AEN2032]

Doctrine allows you to define *portable* constraints on columns and tables. Constraints give you as much control over the data in your tables as you wish. If a user attempts to store data in a column that would violate a constraint, an error is raised. This applies even if the value came from the default value definition.

Doctrine constraints act as database level constraints as well as application level validators. This means double security: the database doesn't allow wrong kind of values and neither does the application.

