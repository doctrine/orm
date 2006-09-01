<?php
// mapping id column

$user = new User();

$user->setAttribute(Doctrine::ATTR_COLL_KEY, 'id');

// now user collections will use the values of
// id column as element indexes

$users = $user->getTable()->findAll();

foreach($users as $id => $user) {
    print $id . $user->name;
}

// mapping name column

$user = new User();

$user->setAttribute(Doctrine::ATTR_COLL_KEY, 'name');

// now user collections will use the values of
// name column as element indexes

$users = $user->getTable()->findAll();

foreach($users as $name => $user) {
    print $name . $user->type;
}
?>
