The type and length validations are handy but most of the time they're not enough. Therefore
Doctrine provides some mechanisms that can be used to validate your data in more detail.<br />
<br />
Validators: Validators are an easy way to specify further validations. Doctrine has a lot of predefined
validators that are frequently needed such as email, country, ip, range and regexp validators. You
find a full list of available validators at the bottom of this page. You can specify which validators
apply to which column through the 4th argument of the hasColumn() method.
If that is still not enough and you need some specialized validation that is not yet available as
a predefined validator you have three options:<br />
<br />
- You can write the validator on your own.<br />
- You can propose your need for a new validator to a Doctrine developer.<br />
- You can use validation hooks.<br />
<br />
The first two options are advisable if it is likely that the validation is of general use
and is potentially applicable in many situations. In that case it is a good idea to implement
a new validator. However if the validation is special it is better to use hooks provided by Doctrine:<br />
<br />
- validate() (Executed every time the record gets validated)<br />
- validateOnInsert() (Executed when the record is new and gets validated)<br />
- validateOnUpdate() (Executed when the record is not new and gets validated)<br />
<br />
If you need a special validation in your active record
you can simply override one of these methods in your active record class (a descendant of Doctrine_Record).
Within thess methods you can use all the power of PHP to validate your fields. When a field
doesnt pass your validation you can then add errors to the record's error stack.
The following code snippet shows an example of how to define validators together with custom
validation:<br />

<code type="php">
class User extends Doctrine_Record {
    public function setUp() {
        $this->ownsOne("Email","User.email_id");
    }
    public function setTableDefinition() {
        // no special validators used only types 
        // and lengths will be validated
        $this->hasColumn("name","string",15);
        $this->hasColumn("email_id","integer");
        $this->hasColumn("created","integer",11);
    }
    // Our own validation
    protected function validate() {
        if ($this->name == 'God') {
            // Blasphemy! Stop that! ;-)
            // syntax: add(<fieldName>, <error code/identifier>)
            $this->getErrorStack()->add('name', 'forbiddenName');
        }
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        // validators 'email' and 'unique' used
        $this->hasColumn("address","string",150, array("email", "unique"));
    }
}  
</code>
