<?php
require_once('playground.php');
require_once('connection.php');
require_once('models.php');

Doctrine_Migration::migration('migration', 1, 3);
Doctrine_Migration::migration('migration', 3, 1);
