Adding custom table classes is very easy. Only thing you need to do is name the classes as [componentName]Table and make them 
inherit Doctrine_Table.

<code type="php">

// valid table object

class UserTable extends Doctrine_Table {

}

// not valid [doesn't extend Doctrine_Table]
class GroupTable { }
</code>


