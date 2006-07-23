<?php
$query->from("User")
      ->where("User.name = ?");
      
$query->execute(array('Jack Daniels'));  
?>
