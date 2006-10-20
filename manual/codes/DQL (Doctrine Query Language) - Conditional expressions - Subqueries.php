<?php
// finding all users which don't belong to any group 1
$query = "FROM User WHERE User.id NOT IN 
                        (SELECT u.id FROM User u 
                         INNER JOIN u.Group g WHERE g.id = ?";
                         
$users = $conn->query($query, array(1));

// finding all users which don't belong to any groups
// Notice: 
// the usage of INNER JOIN
// the usage of empty brackets preceding the Group component

$query = "FROM User WHERE User.id NOT IN 
                        (SELECT u.id FROM User u 
                         INNER JOIN u.Group g)";

$users = $conn->query($query);
?>
