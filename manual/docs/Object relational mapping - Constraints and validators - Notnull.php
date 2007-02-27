A not-null constraint simply specifies that a column must not assume the null value. A not-null constraint is always written as a column constraint.

The following definition uses a notnull constraint for column 'name'. This means that the specified column doesn't accept
null values.

<code type='php'>
class User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200, array('notnull' => true,
                                                      'primary' => true));
    }
}
</code>

When this class gets exported to database the following Sql statement would get executed (in Mysql):

CREATE TABLE user (name VARCHAR(200) NOT NULL, PRIMARY KEY(name))
