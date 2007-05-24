<?php
require_once dirname(__FILE__) . '/../lib/Doctrine.php';


error_reporting(E_ALL);

spl_autoload_register(array('Doctrine', 'autoload'));
require_once 'classes.php';
require_once dirname(__FILE__) . '/../models/location.php';

print "<pre>";

$manager = Doctrine_Manager::getInstance();
$dbh = Doctrine_Db::getConnection('sqlite::memory:');
$conn = $manager->openConnection($dbh);

$user = new User();
$user->name = 'zYne';
$user->Phonenumber[0]->phonenumber = '123 123';
if ($user === $user->Phonenumber[0]->entity_id) {
    print 'case 1 works\n';
}
$city = new Record_City();
$city->name = 'City 1';
$city->District->name = 'District 1';

if ($city->District === $city->district_id) {
    print 'case 2 works\n';
}


$c = new Record_Country();
$c->name = 'Some country';
$c->City[0]->name = 'City 1';
$c->City[0]->District->name = 'District 1';

print $c->City[0]->District . "\n";
print $c->City[0]->get('district_id'). "\n";

if ($c->City[0]->get('district_id') == $c->City[0]->District) {
    print "case 3 works!\n";
}
