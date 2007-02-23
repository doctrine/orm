Binding One-To-One foreign key associations is done with Doctrine_Record::ownsOne() and Doctrine_Record::hasOne() methods.
In the following example user owns one email and has one address. So the relationship between user and email is one-to-one composite.
The relationship between user and address is one-to-one aggregate.
<br \><br \>
The Email component here is mapped to User component's column email_id hence their relation is called LOCALKEY relation. 
On the other hand the Address component is mapped to User by it's user_id column hence the relation between User and Address is called
FOREIGNKEY relation.
