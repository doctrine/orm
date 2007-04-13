<?php ?>
Doctrine supports default values for all data types. When default value is attached to a record column this means two of things.
First this value is attached to every newly created Record.



<code type="php">
<?php 
class User extends Doctrine_record { 
    public function setTableDefinition() {
        \$this->hasColumn('name', 'string', 50, array('default' => 'default name'));
    } 
}

\$user = new User();
print \$user->name; // default name
?></code>


Also when exporting record class to database DEFAULT //value// is attached to column definition statement. 


