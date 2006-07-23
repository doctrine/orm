<?php
// setting default event listener

$manager->setAttribute(Doctrine::ATTR_LISTENER, new MyListener());
?>
