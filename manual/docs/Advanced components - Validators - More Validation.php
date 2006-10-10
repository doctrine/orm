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
a new validator. However if the validation is special it is better to use hooks provided by Doctrine.
One of these hooks is the validate() method. If you need a special validation in your active record
you can simply override validate() in your active record class (a descendant of Doctrine_Record).
Within this method you can use all the power of PHP to validate your fields. When a field
doesnt pass your validation you can then add errors to the record's error stack.
The following code snippet shows an example of how to define validators together with custom
validation:<br />
