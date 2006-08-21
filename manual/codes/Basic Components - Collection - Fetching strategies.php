<?php
$table = $conn->getTable("User");

$table->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_IMMEDIATE);

$users = $table->findAll();

// or

$users = $conn->query("FROM User-I"); // immediate collection

foreach($users as $user) {
    print $user->name;
}


$table->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_LAZY);

$users = $table->findAll();

// or

$users = $conn->query("FROM User-L"); // lazy collection

foreach($users as $user) {
    print $user->name;
}

$table->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_BATCH);

$users = $table->findAll();

// or

$users = $conn->query("FROM User-B"); // batch collection

foreach($users as $user) {
    print $user->name;
}

$table->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_OFFSET);

$users = $table->findAll();

// or

$users = $conn->query("FROM User-O"); // offset collection

foreach($users as $user) {
    print $user->name;
}
?>
