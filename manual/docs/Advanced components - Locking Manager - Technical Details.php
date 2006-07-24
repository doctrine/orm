The pessimistic offline locking manager stores the locks in the database (therefore 'offline').
The required locking table is automatically created when you try to instantiate an instance
of the manager and the ATTR_CREATE_TABLES is set to TRUE.
This behaviour may change in the future to provide a centralised and consistent table creation
procedure for installation purposes.
