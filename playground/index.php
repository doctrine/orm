<?php
require_once('playground.php');
require_once('connection.php');

class MigrationTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        
    }
}

$migrate = new Doctrine_Migration('/Users/jwage/Sites/doctrine_trunk/playground/migration_classes');
$migrate->migrate();