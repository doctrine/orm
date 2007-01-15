<p>Managing tree structures in doctrine is easy. Doctrine currently fully supports Nested Set, and plans to support the other implementations soon. To set your model to act as a tree, simply add the code below to your models table definition.</p>

<p>Now that Doctrine knows that this model acts as a tree, it will automatically add any required columns for your chosen implementation, so you do not need to set any tree specific columns within your table definition.</p>

<p>Doctrine has standard interface's for managing tree's, that are used by all the implementations. Every record in the table represents a node within the tree (the table), so doctrine provides two interfaces, Tree and Node.</p>