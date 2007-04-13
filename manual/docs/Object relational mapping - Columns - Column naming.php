One problem with database compatibility is that many databases differ in their behaviour of how the result set of a
query is returned. MySql leaves the field names unchanged, which means if you issue a query of the form
"SELECT myField FROM ..." then the result set will contain the field 'myField'.




Unfortunately, this is just the way MySql and some other databases do it. Postgres for example returns all field names in lowercase
whilst Oracle returns all field names in uppercase. "So what? In what way does this influence me when using Doctrine?",
you may ask. Fortunately, you don't have to bother about that issue at all. 


Doctrine takes care of this problem
transparently. That means if you define a derived Record class and define a field called 'myField' you will always
access it through $record->myField (or $record['myField'], whatever you prefer) no matter whether you're using MySql
or Postgres or Oracle ect.



In short: You can name your fields however you want, using under_scores, camelCase or whatever you prefer. 

