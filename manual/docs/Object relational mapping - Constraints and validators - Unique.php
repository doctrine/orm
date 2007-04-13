Unique constraints ensure that the data contained in a column or a group of columns is unique with respect to all the rows in the table.

In general, a unique constraint is violated when there are two or more rows in the table where the values of all of the columns included in the constraint are equal. However, two null values are not considered equal in this comparison. That means even in the presence of a unique constraint it is possible to store duplicate rows that contain a null value in at least one of the constrained columns. This behavior conforms to the SQL standard, but some databases do not follow this rule. So be careful when developing applications that are intended to be portable.

The following definition uses a unique constraint for column 'name'.

<code type='php'>
class User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200, array('unique' => true));
    }
}
</code>

>> Note: You should only use unique constraints for other than primary key columns. Primary key columns are always unique.

The following definition adds a unique constraint for columns 'name' and 'age'.

<code type='php'>
class User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('age', 'integer', 2);
        
        $this->unique(array('name', 'age'));
    }
}
</code>
