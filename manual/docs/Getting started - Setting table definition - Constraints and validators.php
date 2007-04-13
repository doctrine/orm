Following attributes are available for columns


|| <b class='title' valign='top'>name** || <b class='title' valign='top'>args** || <b class='title'>description** ||
|||||| &raquo;&raquo; Basic attributes ||
|| **primary** || bool true || Defines column as a primary key column. ||
|| **autoincrement** || bool true || Defines column as autoincremented column. If the underlying database doesn't support autoincrementation natively its emulated with triggers and sequence tables.
|| **default** || mixed default || Sets //default// as an application level default value for a column. When default value has been set for a column every time a record is created the specified column has the //default// as its value.
|| **zerofill** || boolean zerofill || Defines column as zerofilled column. Only supported by some drivers.
|| **unsigned** || boolean true || Defines column with integer type as unsigned. Only supported by some drivers.
|| **fixed** || boolean true || Defines string typed column as fixed length.
|| **enum** || array enum || Sets //enum// as an application level enum value list for a column.
|||||| &raquo;&raquo; Basic validators ||
|| **unique** || bool true || Acts as database level unique constraint. Also validates that the specified column is unique.
|| **nospace** || bool true || Nospace validator. This validator validates that specified column doesn't contain any space/newline characters. 

|| **notblank** || bool true || Notblank validator. This validator validates that specified column doesn't contain only space/newline characters. Useful in for example comment posting applications where users are not allowed to post empty comments.
|| **notnull** || bool true || Acts as database level notnull constraint as well as notnull validator for the specified column.
|||||| &raquo;&raquo; Advanced validators ||
|| **email** || bool true || Email validator. Validates that specified column is a valid email address.
|| **date** || bool true || Date validator.
|| **range** || array(min, max) || Range validator. Validates that the column is between //min// and //max//.
|| **country** || bool true || Country code validator validates that specified column has a valid country code.
|| **regexp ** || string regexp || Regular expression validator validates that specified column matches //regexp//.
|| **ip** || bool true || Ip validator validates that specified column is a valid internet protocol address.
|| **usstate** || bool true || Usstate validator validates that specified column is a valid usstate.



<code type="php">
class User extends Doctrine_Record {
    public function setTableDefinition() {
        // the name cannot contain whitespace
        $this->hasColumn("name", "string", 50, array("nospace" => true));

        // the email should be a valid email
        $this->hasColumn("email", "string", 200, array("email" => true));

        // home_country should be a valid country code and not null
        $this->hasColumn("home_country", "string", 2, array("country" => true, "notnull" => true));

    }
}
</code>
