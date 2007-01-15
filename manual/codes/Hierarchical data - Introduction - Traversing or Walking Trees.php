<?php
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

?>