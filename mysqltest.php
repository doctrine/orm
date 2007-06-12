<?php
require_once('lib/Doctrine.php');

spl_autoload_register(array('Doctrine', 'autoload'));

$dbh = new PDO('mysql:host=localhost;dbname=test', 'root', 'dc34');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
/**
$dbh->query('TRUNCATE TABLE user');
$dbh->query('TRUNCATE TABLE club');
$dbh->query('TRUNCATE TABLE clubuser');
 */
$dbh->query('DROP TABLE IF EXISTS clubuser CASCADE');
$dbh->query('DROP TABLE IF EXISTS club');
$dbh->query('DROP TABLE IF EXISTS user');

$dbh->query('DROP TRIGGER IF EXISTS trg1');
$dbh->query('DROP TRIGGER IF EXISTS trg2');


$dbh->query('CREATE TABLE IF NOT EXISTS user (id INT, parent_id INT, name VARCHAR(200), PRIMARY KEY(id), INDEX (id))');
$dbh->query('CREATE TABLE IF NOT EXISTS club (id INT, name VARCHAR(200), PRIMARY KEY(id), INDEX (id))');
$dbh->query('CREATE TABLE IF NOT EXISTS clubuser (user_id INT, club_id INT, INDEX (user_id), INDEX (club_id), PRIMARY KEY(user_id, club_id))');
$dbh->query('ALTER TABLE clubuser ADD FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');
$dbh->query('ALTER TABLE clubuser ADD FOREIGN KEY (club_id) REFERENCES club(id) ON DELETE CASCADE');
$dbh->query('ALTER TABLE user ADD FOREIGN KEY (parent_id) REFERENCES user(id) ON DELETE CASCADE');
/**
$dbh->query('CREATE TRIGGER trg5 AFTER DELETE ON user
             FOR EACH ROW BEGIN
             DELETE FROM user WHERE user.parent_id = OLD.id;
             END;');

$dbh->query('CREATE TRIGGER trg2 BEFORE DELETE ON user
             FOR EACH ROW BEGIN
             DELETE FROM clubuser WHERE clubuser.user_id = OLD.id;
             END;');
*/
/**
$dbh->query('CREATE TRIGGER trg1 BEFORE DELETE ON club
             FOR EACH ROW BEGIN 
             @param := SELECT user_id FROM clubuser WHERE clubuser.club_id = OLD.id;
             DELETE FROM clubuser WHERE clubuser.user_id = OLD.id;
             DELETE FROM clubuser WHERE clubuser.club_id = OLD.id;
             END;');
                                IN (SELECT user_id FROM clubuser WHERE clubuser.club_id = OLD.id);


$dbh->query('CREATE TRIGGER trg3 AFTER DELETE ON clubuser
             FOR EACH ROW BEGIN
             UPDATE user SET user.name = "deleted" WHERE user.id = OLD.user_id;
             END;');

$dbh->query('CREATE TRIGGER trg4 AFTER UPDATE ON user
             FOR EACH ROW BEGIN
             DELETE FROM clubuser WHERE clubuser.user_id = NEW.id;
             DELETE FROM user WHERE user.name = "deleted";
             END;');
*/
$dbh->query('INSERT INTO user (id, parent_id) VALUES (1, 1)');
$dbh->query('INSERT INTO user (id, parent_id) VALUES (2, 1)');
$dbh->query('INSERT INTO user (id, parent_id) VALUES (3, 1)');  
$dbh->query('INSERT INTO user (id, parent_id) VALUES (4, 3)');
$dbh->query('INSERT INTO user (id, parent_id) VALUES (5, 2)');
$dbh->query('INSERT INTO user (id, parent_id) VALUES (6, NULL)');  
$dbh->query('INSERT INTO user (id, parent_id) VALUES (7, 6)');
$dbh->query('INSERT INTO user (id, parent_id) VALUES (8, 6)');

$dbh->query('INSERT INTO club (id) VALUES (1)');
$dbh->query('INSERT INTO club (id) VALUES (2)');
$dbh->query('INSERT INTO club (id) VALUES (3)');
$dbh->query('INSERT INTO club (id) VALUES (4)');

$dbh->query('INSERT INTO clubuser (user_id, club_id) VALUES (1, 1)');
$dbh->query('INSERT INTO clubuser (user_id, club_id) VALUES (2, 1)');
$dbh->query('INSERT INTO clubuser (user_id, club_id) VALUES (2, 2)');


print "<pre>";

//$dbh->query('DELETE FROM club WHERE id = 1');
$dbh->query('DELETE FROM user WHERE id = 1');
// should have deleted the first two users
var_dump($dbh->query('SELECT * FROM user')->fetchAll());
?>
