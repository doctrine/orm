In the following example we make a user management system where
<br /><br />
1. Each user and group are entities
<br /><br />
2. User is an entity of type 0
<br /><br />
3. Group is an entity of type 1
<br /><br />
4. Each entity (user/group) has 0-1 email
<br /><br />
5. Each entity has 0-* phonenumbers
<br /><br />
6. If an entity is saved all its emails and phonenumbers are also saved
<br /><br />
7. If an entity is deleted all its emails and phonenumbers are also deleted
<br /><br />
8. When an entity is created and saved a current timestamp will be assigned to 'created' field
<br /><br />
9. When an entity is updated a current timestamp will be assigned to 'updated' field
<br /><br />
10. Entities will always be fetched in batches
