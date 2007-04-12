<?php
// SELECT u.*, COUNT(p.id) num_posts FROM User u, u.Posts p WHERE u.id = 1 GROUP BY u.id

$query = new Doctrine_Query();

$query->select('u.*, COUNT(p.id) num_posts')
      ->from('User u, u.Posts p')
      ->where('u.id = ?', 1)
      ->groupby('u.id')
      ->execute();

echo $query->Posts[0]->num_posts . ' posts found';
?>

