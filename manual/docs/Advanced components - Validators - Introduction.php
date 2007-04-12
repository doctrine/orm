Validation in Doctrine is a way to enforce your business rules in the model part of the MVC architecture.
You can think of this validation as a gateway that needs to be passed right before data gets into the
persistent data store. The definition of these business rules takes place at the record level, that means
in your active record model classes (classes derived from Doctrine_Record).
The first thing you need to do to be able to use this kind of validation is to enable it globally.
This is done through the Doctrine_Manager (see the code below).<br />
<br />
Once you enabled validation, you'll get a bunch of validations automatically:<br />
<br />
- Data type validations: All values assigned to columns are checked for the right type. That means
if you specified a column of your record as type 'integer', Doctrine will validate that
any values assigned to that column are of this type. This kind of type validation tries to
be as smart as possible since PHP is a loosely typed language. For example 2 as well as "7"
are both valid integers whilst "3f" is not. Type validations occur on every column (since every
column definition needs a type).<br /><br />
- Length validation: As the name implies, all values assigned to columns are validated to make
sure that the value does not exceed the maximum length.

<code type="php">
// turning on validation

Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_VLD, true);
</code>
