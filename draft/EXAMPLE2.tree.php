<?php
/*please note that this is a very DRAFT and basic example of how you can use the different functions available in the tree implentation with many roots in one table*/

require_once("../lib/Doctrine.php");
 
// autoloading objects, modified function to search drafts folder first, should run this test script from the drafts folder
function __autoload($classname) {

        if (class_exists($classname)) {
            return false;
        }
        if ( !  $path) {
            $path = dirname(__FILE__);
        }
        $classpath = str_replace('Doctrine_', '',$classname);
        
        $class = $path.DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR,$classpath) . '.php';

        if ( !file_exists($class)) {
            return Doctrine::autoload($classname);
        }

        require_once($class);

        return true;
}

// define our tree
class Menu extends Doctrine_Record { 
    public function setTableDefinition() {

        $this->setTableName('menu_many_roots');

        // add this your table definition to set the table as NestedSet tree implementation
        // with many roots
        $this->actsAsTree('NestedSet', array('has_many_roots' => true));
       
        // you do not need to add any columns specific to the nested set implementation, these are added for you
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

// refresh as node's lft and rgt values have changed, zYne, can we automate this?
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

output_message('initial root');
output_tree($root);

// create a new root with a tree
$root2 = new Menu();
$root2->set('name', 'new root');

$manager->getTable('Menu')->getTree()->createRoot($root2);

// build tree
$two2 = new Menu();
$two2->set('name', '2');
$root2->getNode()->addChild($two2);

$one2 = new Menu();
$one2->set('name', '1');
$one2->getNode()->insertAsPrevSiblingOf($two2);

// refresh as node's lft and rgt values have changed, zYne, can we automate this?
$two2->refresh();

$three2 = new Menu();
$three2->set('name', '3');
$three2->getNode()->insertAsNextSiblingOf($two2);
$two2->refresh();

$one_one2 = new Menu();
$one_one2->set('name', '1.1');
$one_one2->getNode()->insertAsFirstChildOf($one2);
$one2->refresh();

$one_two2 = new Menu();
$one_two2->set('name', '1.2');
$one_two2->getNode()->insertAsLastChildOf($one2);
$one_two2->refresh();

$one_two_one2 = new Menu();
$one_two_one2->set('name', '1.2.1');
$one_two2->getNode()->addChild($one_two_one2);

$root2->refresh();
$four2 = new Menu();
$four2->set('name', '4');
$root2->getNode()->addChild($four2);

output_message('new root');
output_tree($root2);

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

output_message('transformed initial root');
output_tree($root);

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
  echo str_repeat('-', $record->getNode()->getLevel()) . $record->get('name') . ' (has children:'.$record->getNode()->hasChildren().') '. ' (is leaf:'.$record->getNode()->isLeaf().') '.'<br/>';  
}

function output_message($msg)
{
  echo "<br /><strong><em>$msg</em></strong>".'<br />';
}




