Doctrine check constraints act as database level constraints as well as application level validators. When a record with check validators is exported additional CHECK constraints are being added to CREATE TABLE statement.

Doctrine provides the following simple check operators:

* '''gt'''
> greater than constraint ( > )
* '''lt'''
> less than constraint ( < )
* '''gte'''
> greater than or equal to constraint ( >= )
* '''lte'''
> less than or equal to constraint ( <= )


Consider the following example:

<code type='php'>
class Product extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('id', 'integer', 4, 'primary');
        $this->hasColumn('price', 'decimal', 18, array('gt' => 0);
    }
}
</code>

When exported the given class definition would execute the following statement (in pgsql):

CREATE TABLE product (
    id INTEGER,
    price NUMERIC CHECK (price > 0)
    PRIMARY KEY(id))

So Doctrine optionally ensures even at the database level that the price of any product cannot be below zero.

> NOTE: some databases don't support CHECK constraints. When this is the case Doctrine simple skips the creation of check constraints.

If the Doctrine validators are turned on the given definition would also ensure that when a record is being saved its price is always greater than zero.

If some of the prices of the saved products within a transaction is below zero, Doctrine throws Doctrine_Validator_Exception and automatically rolls back the transaction.
