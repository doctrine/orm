<?php

// valid table object

class UserTable extends Doctrine_Table {

}

// not valid [doesn't extend Doctrine_Table]
class GroupTable { }
?>


