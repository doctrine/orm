<?php ?>
Doctrine supports default values for all data types. When default value is attached to a record column this means two of things.
First this value is attached to every newly created Record.
<br \><br \>
<?php
renderCode("<?php
class User {
    public function setTableDefinition() {
        \$this->hasColumn('name', 'string', 50, 'default name');
    }
}

\$user = new User();
print \$user->name; // default name
?>");
?>
<br \>
Also when exporting record class to database DEFAULT <i>value</i> is attached to column definition statement. <br \>
