<?php
/** 
 * lets presume $users contains a collection of users
 * each having 0-1 email and 0-* phonenumbers
 */
$users->delete();
/**
 * On connection drivers other than mysql doctrine would now perform three queries
 * regardless of how many users, emails and phonenumbers there are
 *
 * the queries would look something like:
 * DELETE FROM entity WHERE entity.id IN (1,2,3, ... ,15)
 * DELETE FROM phonenumber WHERE phonenumber.id IN (4,6,7,8)
 * DELETE FROM email WHERE email.id IN (1,2, ... ,10)
 *
 * On mysql doctrine is EVEN SMARTER! Now it would perform only one query!
 * the query would look like:
 * DELETE entity, email, phonenumber FROM entity 
 * LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id, email
 * WHERE (entity.email_id = email.id) && (entity.id IN(4, 5, 6, 7, 8, 9, 10, 11)) && (entity.type = 0)
 */


?>
