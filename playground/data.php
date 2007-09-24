<?php
$groups = new Doctrine_Collection($conn->getTable('Group'));

$groups[0]->name = 'Drama Actors';

$groups[1]->name = 'Quality Actors';


$groups[2]->name = 'Action Actors';
$groups[2]['Phonenumber'][0]->phonenumber = '123 123';
$groups->save();

$users = new Doctrine_Collection('User');

$users[0]->name = 'zYne';
$users[0]['Email']->address = 'zYne@example.com';
$users[0]['Phonenumber'][0]->phonenumber = '123 123';

$users[1]->name = 'Arnold Schwarzenegger';
$users[1]->Email->address = 'arnold@example.com';
$users[1]['Phonenumber'][0]->phonenumber = '123 123';
$users[1]['Phonenumber'][1]->phonenumber = '456 456';
$users[1]->Phonenumber[2]->phonenumber = '789 789';
$users[1]->Group[0] = $groups[2];

$users[2]->name = 'Michael Caine';
$users[2]->Email->address = 'caine@example.com';
$users[2]->Phonenumber[0]->phonenumber = '123 123';

$users[3]->name = 'Takeshi Kitano';
$users[3]->Email->address = 'kitano@example.com';
$users[3]->Phonenumber[0]->phonenumber = '111 222 333';

$users[4]->name = 'Sylvester Stallone';
$users[4]->Email->address = 'stallone@example.com';
$users[4]->Phonenumber[0]->phonenumber = '111 555 333';
$users[4]['Phonenumber'][1]->phonenumber = '123 213';
$users[4]['Phonenumber'][2]->phonenumber = '444 555';

$users[5]->name = 'Kurt Russell';
$users[5]->Email->address = 'russell@example.com';
$users[5]->Phonenumber[0]->phonenumber = '111 222 333';

$users[6]->name = 'Jean Reno';
$users[6]->Email->address = 'reno@example.com';
$users[6]->Phonenumber[0]->phonenumber = '111 222 333';
$users[6]['Phonenumber'][1]->phonenumber = '222 123';
$users[6]['Phonenumber'][2]->phonenumber = '123 456';

$users[7]->name = 'Edward Furlong';
$users[7]->Email->address = 'furlong@example.com';
$users[7]->Phonenumber[0]->phonenumber = '111 567 333';

$users->save();