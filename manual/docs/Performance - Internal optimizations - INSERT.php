
<code type="php">
// lets presume $users contains a collection of new users
// each having 0-1 email and 0-* phonenumbers
$users->save();
/**
 * now doctrine would perform prepared queries in the following order:
 *
 * first the emails since every user needs to get the primary key of their newly created email
 * INSERT INTO email (address) VALUES (:address)
 * INSERT INTO email (address) VALUES (:address)
 * INSERT INTO email (address) VALUES (:address)
 * 
 * then the users
 * INSERT INTO entity (name,email_id) VALUES (:name,:email_id)
 * INSERT INTO entity (name,email_id) VALUES (:name,:email_id)
 * INSERT INTO entity (name,email_id) VALUES (:name,:email_id)
 *
 * and at last the phonenumbers since they need the primary keys of the newly created users
 * INSERT INTO phonenumber (phonenumber,entity_id) VALUES (:phonenumber,:entity_id)
 * INSERT INTO phonenumber (phonenumber,entity_id) VALUES (:phonenumber,:entity_id)
 * INSERT INTO phonenumber (phonenumber,entity_id) VALUES (:phonenumber,:entity_id)
 * INSERT INTO phonenumber (phonenumber,entity_id) VALUES (:phonenumber,:entity_id)
 * INSERT INTO phonenumber (phonenumber,entity_id) VALUES (:phonenumber,:entity_id)
 *
 * These operations are considerably fast, since many databases perform multiple
 * prepared queries very rapidly
 */
</code>
