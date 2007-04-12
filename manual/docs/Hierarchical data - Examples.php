
<code type="php">

require_once("path/to/Doctrine.php");
 
function __autoload($classname) {

    return Doctrine::autoload($classname);

}

// define our tree
class Menu extends Doctrine_Record { 
    public function setTableDefinition() {

        $this->setTableName('menu');

        // add this your table definition to set the table as NestedSet tree implementation
        $this->actsAsTree('NestedSet');
       
        // you do not need to add any columns specific to the nested set implementation
		// these are added for you
        $this->hasColumn("name","string",30);

    }
    
    // this __toString() function is used to get the name for the path, see node::getPath
    public function __toString() {
        return $this->get('name');
    }
}

// set connections to database
$dsn = 'mysql:dbname=nestedset;host=localhost';
$user = 'user';
$password = 'pass';
 
try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
 
$manager = Doctrine_Manager::getInstance();
 
$conn = $manager->openConnection($dbh);

// create root
$root = new Menu();
$root->set('name', 'root');

$manager->getTable('Menu')->getTree()->createRoot($root);

// build tree
$two = new Menu();
$two->set('name', '2');
$root->getNode()->addChild($two);

$one = new Menu();
$one->set('name', '1');
$one->getNode()->insertAsPrevSiblingOf($two);

// refresh as node's lft and rgt values have changed
$two->refresh();

$three = new Menu();
$three->set('name', '3');
$three->getNode()->insertAsNextSiblingOf($two);
$two->refresh();

$one_one = new Menu();
$one_one->set('name', '1.1');
$one_one->getNode()->insertAsFirstChildOf($one);
$one->refresh();

$one_two = new Menu();
$one_two->set('name', '1.2');
$one_two->getNode()->insertAsLastChildOf($one);
$one_two->refresh();

$one_two_one = new Menu();
$one_two_one->set('name', '1.2.1');
$one_two->getNode()->addChild($one_two_one);

$root->refresh();
$four = new Menu();
$four->set('name', '4');
$root->getNode()->addChild($four);

$root->refresh();
$five = new Menu();
$five->set('name', '5');
$root->getNode()->addChild($five);

$root->refresh();
$six = new Menu();
$six->set('name', '6');
$root->getNode()->addChild($six);

output_message('initial tree');
output_tree($root);

$one_one->refresh();
$six->set('name', '1.0 (was 6)');
$six->getNode()->moveAsPrevSiblingOf($one_one);

$one_two->refresh();
$five->refresh();
$five->set('name', '1.3 (was 5)');
$five->getNode()->moveAsNextSiblingOf($one_two);

$one_one->refresh();
$four->refresh();
$four->set('name', '1.1.1 (was 4)');
$four->getNode()->moveAsFirstChildOf($one_one);

$root->refresh();
$one_two_one->refresh();
$one_two_one->set('name', 'last (was 1.2.1)');
$one_two_one->getNode()->moveAsLastChildOf($root);

output_message('transformed tree');
output_tree($root);

$one_one->refresh();
$one_one->deleteNode();

output_message('delete 1.1');
output_tree($root);

// now test fetching root
$tree_root = $manager->getTable('Menu')->getTree()->findRoot();
output_message('testing fetch root and outputting tree from the root node');
output_tree($tree_root);

// now test fetching the tree
output_message('testing fetching entire tree using tree::fetchTree()');
$tree = $manager->getTable('Menu')->getTree()->fetchTree();
while($node = $tree->next())
{
  output_node($node);
}

// now test fetching the tree
output_message('testing fetching entire tree using tree::fetchTree(), excluding root node');
$tree = $manager->getTable('Menu')->getTree()->fetchTree(array('include_record' => false));
while($node = $tree->next())
{
  output_node($node);
}

// now test fetching the branch
output_message('testing fetching branch for 1, using tree::fetchBranch()');
$one->refresh();
$branch = $manager->getTable('Menu')->getTree()->fetchBranch($one->get('id'));
while($node = $branch->next())
{
  output_node($node);
}

// now test fetching the tree
output_message('testing fetching branch for 1, using tree::fetchBranch() excluding node 1');
$tree = $manager->getTable('Menu')->getTree()->fetchBranch($one->get('id'), array('include_record' => false));
while($node = $tree->next())
{
  output_node($node);
}

// now perform some tests
output_message('descendants for 1');
$descendants = $one->getNode()->getDescendants();
while($descendant = $descendants->next())
{
  output_node($descendant);
}

// move one and children under two
$two->refresh();
$one->getNode()->moveAsFirstChildOf($two);

output_message('moved one as first child of 2');
output_tree($root);

output_message('descendants for 2');
$two->refresh();
$descendants = $two->getNode()->getDescendants();
while($descendant = $descendants->next())
{
  output_node($descendant);
}

output_message('number descendants for 2');
echo $two->getNode()->getNumberDescendants() .'</br>';

output_message('children for 2 (notice excludes children of children, known as descendants)');
$children = $two->getNode()->getChildren();
while($child = $children->next())
{
  output_node($child);
}

output_message('number children for 2');
echo $two->getNode()->getNumberChildren() .'</br>';

output_message('path to 1');
$path = $one->getNode()->getPath(' > ');
echo $path .'<br />';

output_message('path to 1 (including 1)');
$path = $one->getNode()->getPath(' > ', true);
echo $path .'<br />';

output_message('1 has parent');
$hasParent = $one->getNode()->hasParent();
$msg = $hasParent ? 'true' : 'false';
echo $msg . '</br/>';

output_message('parent to 1');
$parent = $one->getNode()->getParent();
if($parent->exists())
{
  echo $parent->get('name') .'<br />';  
}

output_message('root isRoot?');
$isRoot = $root->getNode()->isRoot();
$msg = $isRoot ? 'true' : 'false';
echo $msg . '</br/>';

output_message('one isRoot?');
$isRoot = $one->getNode()->isRoot();
$msg = $isRoot ? 'true' : 'false';
echo $msg . '</br/>';

output_message('root hasParent');
$hasParent = $root->getNode()->hasParent();
$msg = $hasParent ? 'true' : 'false';
echo $msg . '</br/>';

output_message('root getParent');
$parent = $root->getNode()->getParent();
if($parent->exists())
{
  echo $parent->get('name') .'<br />';  
}

output_message('get first child of root');
$record = $root->getNode()->getFirstChild();
if($record->exists())
{
  echo $record->get('name') .'<br />';  
}

output_message('get last child of root');
$record = $root->getNode()->getLastChild();
if($record->exists())
{
  echo $record->get('name') .'<br />';  
}

$one_two->refresh();

output_message('get prev sibling of 1.2');
$record = $one_two->getNode()->getPrevSibling();
if($record->exists())
{
  echo $record->get('name') .'<br />';  
}

output_message('get next sibling of 1.2');
$record = $one_two->getNode()->getNextSibling();
if($record->exists())
{
  echo $record->get('name') .'<br />';  
}

output_message('siblings of 1.2');
$siblings = $one_two->getNode()->getSiblings();
foreach($siblings as $sibling)
{
  if($sibling->exists())
    echo $sibling->get('name') .'<br />'; 
}

output_message('siblings of 1.2 (including 1.2)');
$siblings = $one_two->getNode()->getSiblings(true);
foreach($siblings as $sibling)
{
  if($sibling->exists())
    echo $sibling->get('name') .'<br />'; 
}

$new = new Menu();
$new->set('name', 'parent of 1.2');
$new->getNode()->insertAsParentOf($one_two);

output_message('added a parent to 1.2');
output_tree($root);

try {
  $dummy = new Menu();
  $dummy->set('name', 'dummy');
  $dummy->save();  
}
catch (Doctrine_Exception $e)
{
  output_message('You cannot save a node unless it is in the tree');
}

try {
  $fake = new Menu();
  $fake->set('name', 'dummy');
  $fake->set('lft', 200);
  $fake->set('rgt', 1);
  $fake->save();  
}
catch (Doctrine_Exception $e)
{
  output_message('You cannot save a node with bad lft and rgt values');
}

// check last remaining tests
output_message('New parent is descendant of 1');
$one->refresh();
$res = $new->getNode()->isDescendantOf($one);
$msg = $res ? 'true' : 'false';
echo $msg . '</br/>';

output_message('New parent is descendant of 2');
$two->refresh();
$res = $new->getNode()->isDescendantOf($two);
$msg = $res ? 'true' : 'false';
echo $msg . '</br/>';

output_message('New parent is descendant of 1.2');
$one_two->refresh();
$res = $new->getNode()->isDescendantOf($one_two);
$msg = $res ? 'true' : 'false';
echo $msg . '</br/>';

output_message('New parent is descendant of or equal to 1');
$one->refresh();
$res = $new->getNode()->isDescendantOfOrEqualTo($one);
$msg = $res ? 'true' : 'false';
echo $msg . '</br/>';

output_message('New parent is descendant of or equal to 1.2');
$one_two->refresh();
$res = $new->getNode()->isDescendantOfOrEqualTo($one_two);
$msg = $res ? 'true' : 'false';
echo $msg . '</br/>';

output_message('New parent is descendant of or equal to 1.3');
$five->refresh();
$res = $new->getNode()->isDescendantOfOrEqualTo($new);
$msg = $res ? 'true' : 'false';
echo $msg . '</br/>';

function output_tree($root)
{
  // display tree
  // first we must refresh the node as the tree has been transformed
  $root->refresh();

  // next we must get the iterator to traverse the tree from the root node
  $traverse = $root->getNode()->traverse();

  output_node($root);
  // now we traverse the tree and output the menu items
  while($item = $traverse->next())
  {
    output_node($item);
  }  

  unset($traverse);
}

function output_node($record)
{
  echo str_repeat('-', $record->getNode()->getLevel()) . $record->get('name') 
						. ' (has children:'.$record->getNode()->hasChildren().') '
						. ' (is leaf:'.$record->getNode()->isLeaf().') '.'<br/>';  
}

function output_message($msg)
{
  echo "<br /><strong><em>$msg</em></strong>".'<br />';
}
</code>