<?php
$sess = Doctrine_Manager::getInstance()->openSession(new PDO("dsn","username","password"));
try {
$sess->beginTransaction();

    // some database operations

$sess->commit();

} catch(Exception $e) {
    $sess->rollback();
}

?>
