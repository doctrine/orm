<?php
$manager = Doctrine_Manager::getInstance();

$manager->setAttribute(Doctrine::ATTR_LISTENER, new MyListener());
?>
