Binding One-To-One foreign key associations is done with Doctrine_Record::ownsOne() and Doctrine_Record::hasOne() methods.
In the following example user owns one email and has one address. So the relationship between user and email is one-to-one composite.
The relationship between user and address is one-to-one aggregate.



The Email component here is mapped to User component's column email_id hence their relation is called LOCALKEY relation.
On the other hand the Address component is mapped to User by it's user_id column hence the relation between User and Address is called
FOREIGNKEY relation.

<code type="php">
class User extends Doctrine_Record {
    public function setUp() {
        $this->hasOne('Address','Address.user_id');
        $this->ownsOne('Email','User.email_id');
        $this->ownsMany('Phonenumber','Phonenumber.user_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('name','string',50);
        $this->hasColumn('loginname','string',20);
        $this->hasColumn('password','string',16);

        // foreign key column for email ID
        $this->hasColumn('email_id','integer');
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('address','string',150);
    }
}
class Address extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('street','string',50);
        $this->hasColumn('user_id','integer');
    }
}
</code>
