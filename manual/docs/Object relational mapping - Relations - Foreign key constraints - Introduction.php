A foreign key constraint specifies that the values in a column (or a group of columns) must match the values appearing in some row of another table. In other words foreign key constraints maintain the referential integrity between two related tables.

Say you have the product table with the following definition:

<code type='php'>
class Product extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('id', 'integer', null, 'primary');
        $this->hasColumn('name', 'string');
        $this->hasColumn('price', 'decimal', 18);
    }
}
</code>

Let's also assume you have a table storing orders of those products. We want to ensure that the order table only contains orders of products that actually exist. So we define a foreign key constraint in the orders table that references the products table:

<code type='php'>
class Order extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('order_id', 'integer', null, 'primary');
        $this->hasColumn('product_id', 'integer');
        $this->hasColumn('quantity', 'integer');
    }
    public function setUp()
    {
        $this->hasOne('Product', 'Order.product_id');

        // foreign key columns should *always* have indexes

        $this->index('product_id', array('fields' => 'product_id'));
    }
}
</code>

When exported the class 'Order' would execute the following sql:

CREATE TABLE orders (
    order_id integer PRIMARY KEY,
    product_id integer REFERENCES products (id),
    quantity integer,
    INDEX product_id_idx (product_id)
)

Now it is impossible to create orders with product_no entries that do not appear in the products table.

We say that in this situation the orders table is the referencing table and the products table is the referenced table. Similarly, there are referencing and referenced columns.
