<?php
// SELECT u.*, COUNT(p.id) num_posts FROM User u, u.Posts p WHERE u.id = 1

$query = new Doctrine_Query();

$query->select('u.*, COUNT(p.id) num_posts')
      ->from('User u, u.Posts p')
      ->where('u.id = ?', 1)
?>
