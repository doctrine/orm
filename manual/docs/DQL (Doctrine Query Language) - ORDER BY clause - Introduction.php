Record collections can be sorted efficiently at the database level using the ORDER BY clause.

Syntax:
<code>
        [ORDER BY {ComponentAlias.columnName}
        [ASC | DESC], ...]
</code>

Examples:


<code>
FROM User.Phonenumber
  ORDER BY User.name, Phonenumber.phonenumber

FROM User u, u.Email e
  ORDER BY e.address, u.id
</code>
In order to sort in reverse order you can add the DESC (descending) keyword to the name of the column in the ORDER BY clause that you are sorting by. The default is ascending order; this can be specified explicitly using the ASC keyword. 


<code>
FROM User u, u.Email e
  ORDER BY e.address DESC, u.id ASC;
</code>
