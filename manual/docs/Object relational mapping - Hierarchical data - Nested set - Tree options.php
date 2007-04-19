

The nested implementation can be configured to allow your table to have multiple root nodes, and therefore multiple trees within the same table.



The example below shows how to setup and use multiple roots based upon the set up above:



<code type="php">
//use these options in the setTableDefinition
$options = array('hasManyRoots' => true,			// enable many roots
	  			 'rootColumnName' => 'root_id');	// set root column name, defaults to 'root_id'

// To create new root nodes, if you have manually set the root_id, then it will be used
// otherwise it will automatically use the next available root id
$root = new Menu();
$root->set('name', 'root');

// insert first root, will auto be assigned root_id = 1
$manager->getTable('Menu')->getTree()->createRoot($root);

$another_root = new Menu();
$another_root->set('name', 'another root');

// insert another root, will auto be assigned root_id = 2
$manager->getTable('Menu')->getTree()->createRoot($another_root);

// fetching a specifc root
$root = $manager->getTable('Menu')->getTree()->fetchRoot(1);
$another_root = $manager->getTable('Menu')->getTree()->fetchRoot(2);

// fetching all roots
$roots = $manager->getTable('Menu')->getTree()->fetchRoots();
</code>
