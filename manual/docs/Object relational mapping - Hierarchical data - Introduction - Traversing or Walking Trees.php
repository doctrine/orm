

You can traverse a Tree in different ways, please see here for more information [http://en.wikipedia.org/wiki/Tree_traversal http://en.wikipedia.org/wiki/Tree_traversal].



The most common way of traversing a tree is Pre Order Traversal as explained in the link above, this is also what is known as walking the tree, this is the default approach when traversing a tree in Doctrine, however Doctrine does plan to provide support for Post and Level Order Traversal (not currently implemented)



<code type="php">
/*
 * traverse the entire tree from root
 */
$root = $manager->getTable('Model')->getTree()->fetchRoot();
if($root->exists())
{
	$tree = $root->traverse();
	while($node = $tree->next())
	{
		// output your tree here
	}
}

// or the optimised approach using tree::fetchTree
$tree = $manager->getTable('Model')->getTree()->fetchTree();
while($node = $tree->next())
{
	// output tree here
}

/*
 * traverse a branch of the tree
 */
$record = $manager->getTable('Model')->find($pk);
if($record->exists())
{
	$branch = $record->traverse();
	while($node = $branch->next())
	{
		// output your tree here
	}
}

// or the optimised approach
$branch = $manager->getTable('Model')->getTree()->fetchBranch($pk);
while($node = $branch->traverse())
{
	// output your tree here
}

</code>