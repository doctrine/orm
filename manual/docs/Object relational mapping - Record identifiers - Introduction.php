Doctrine supports many kind of identifiers. For most cases it is recommended not to 
specify any primary keys (Doctrine will then use field name 'id' as an autoincremented 
primary key). When using table creation Doctrine is smart enough to emulate the
autoincrementation with sequences and triggers on databases that doesn't support it natively.
