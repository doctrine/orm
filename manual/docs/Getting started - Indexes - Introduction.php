Indexes are used to find rows with specific column values quickly. 
Without an index, the database must begin with the first row and then read through the entire table to find the relevant rows. 
<br \><br \>
The larger the table, the more this consumes time. If the table has an index for the columns in question, the database 
can quickly determine the position to seek to in the middle of the data file without having to look at all the data. 
If a table has 1,000 rows, this is at least 100 times faster than reading rows one-by-one.
<br \><br \>
You should *<b>always</b>* use indexes for the fields that are used in sql where conditions.
