<?php
class Entity extends Doctrine_Record {
    public function setUp() {
        $this->ownsOne('Email', 'Entity.email_id');
        $this->ownsMany('Phonenumber', 'Phonenumber.entity_id');
        $this->ownsOne('Account', 'Account.entity_id');
        $this->hasMany('Entity', 'EntityReference.entity1-entity2');
    }
    public function setTableDefinition() {
        $this->hasColumn('id', 'integer',20, 'autoincrement|primary');
        $this->hasColumn('name', 'string',50);
        $this->hasColumn('loginname', 'string',20, array('unique'));
        $this->hasColumn('password', 'string',16);
        $this->hasColumn('type', 'integer',1);
        $this->hasColumn('created', 'integer',11);
        $this->hasColumn('updated', 'integer',11);
        $this->hasColumn('email_id', 'integer');
    }
}
class FieldNameTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('someColumn', 'string', 200, array('default' => 'some string'));
        $this->hasColumn('someEnum', 'enum', 4, array('default' => 'php', 'values' => array('php', 'java', 'python')));
        $this->hasColumn('someArray', 'array', 100, array('default' => array()));
        $this->hasColumn('someObject', 'object', 200, array('default' => new stdClass));
        $this->hasColumn('someInt', 'integer', 20, array('default' => 11));
    }
}
class EntityReference extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('entity1', 'integer', null, 'primary');
        $this->hasColumn('entity2', 'integer', null, 'primary');
        //$this->setPrimaryKey(array('entity1', 'entity2'));
    }
}
class Account extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('entity_id', 'integer');
        $this->hasColumn('amount', 'integer');
    }
}

class EntityAddress extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('entity_id', 'integer');
        $this->hasColumn('address_id', 'integer');
    }
}

class Address extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('User', 'Entityaddress.entity_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('address', 'string',200);
    }
}

// grouptable doesn't extend Doctrine_Table -> Doctrine_Connection
// won't initialize grouptable when Doctrine_Connection->getTable('Group') is called

class GroupTable { }
class Group extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany('User', 'Groupuser.user_id');
        $this->option('inheritanceMap', array('type' => 1));
    }
}
class Error extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany('Description', 'Description.file_md5', 'file_md5');
    }
    public function setTableDefinition() {
        $this->hasColumn('message', 'string',200);
        $this->hasColumn('code', 'integer',11);
        $this->hasColumn('file_md5', 'string',32, 'primary');
    }
}
class Description extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('description', 'string',3000);
        $this->hasColumn('file_md5', 'string',32);
    }
}
class UserTable extends Doctrine_Table { }
class User extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany('Address', 'Entityaddress.address_id');
        $this->ownsMany('Album', 'Album.user_id');
        $this->ownsMany('Book', 'Book.user_id');
        $this->hasMany('Group', 'Groupuser.group_id');
        $this->option('inheritanceMap', array('type' => 0));
    }
    /** Custom validation */
    public function validate() {
        // Allow only one name!
        if ($this->name !== 'The Saint') {
            $this->errorStack()->add('name', 'notTheSaint');
        }
    }
    public function validateOnInsert() {
        if ($this->password !== 'Top Secret') {
            $this->errorStack()->add('password', 'pwNotTopSecret');
        }
    }
    public function validateOnUpdate() {
        if ($this->loginname !== 'Nobody') {
            $this->errorStack()->add('loginname', 'notNobody');
        }
    }
}
class Groupuser extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('added', 'integer');
        $this->hasColumn('group_id', 'integer');
        $this->hasColumn('user_id', 'integer');
    }
}
class Phonenumber extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('phonenumber', 'string',20);
        $this->hasColumn('entity_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('Entity', 'Phonenumber.entity_id');
    }
}

class Element extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('parent_id', 'integer');
    }
    public function setUp() {
        $this->hasMany('Element as Child', 'Child.parent_id');
        $this->hasOne('Element as Parent', 'Element.parent_id');
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('address', 'string',150, 'email|unique');
    }
}
class Book extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany('Author', 'Author.book_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}
class Author extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('book_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}
class Album extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany('Song', 'Song.album_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}
class Song extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('album_id', 'integer');
        $this->hasColumn('genre', 'string',20);
        $this->hasColumn('title', 'string',30);
    }
}

class Task extends Doctrine_Record {
   public function setUp() {
      $this->hasMany('Resource as ResourceAlias', 'Assignment.resource_id');
      $this->hasMany('Task as Subtask', 'Subtask.parent_id');
   } 
   public function setTableDefinition() {
      $this->hasColumn('name', 'string',100); 
      $this->hasColumn('parent_id', 'integer'); 
   }
} 

class Resource extends Doctrine_Record {
   public function setUp() {
      $this->hasMany('Task as TaskAlias', 'Assignment.task_id');
      $this->hasMany('ResourceType as Type', 'ResourceReference.type_id');
   }
   public function setTableDefinition() {
      $this->hasColumn('name', 'string',100);
   }
}
class ResourceReference extends Doctrine_Record {
    public function setTableDefinition() {
       $this->hasColumn('type_id', 'integer');
       $this->hasColumn('resource_id', 'integer');
    }
}
class ResourceType extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('Resource as ResourceAlias', 'ResourceReference.resource_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('type', 'string',100);
    }
}
class Assignment extends Doctrine_Record {
    public function setTableDefinition() {
       $this->hasColumn('task_id', 'integer'); 
       $this->hasColumn('resource_id', 'integer'); 
    } 
}
class Forum_Category extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('root_category_id', 'integer', 10);
        $this->hasColumn('parent_category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('description', 'string', 99999);
    }
    public function setUp() {
        $this->hasMany('Forum_Category as Subcategory', 'Subcategory.parent_category_id');
        $this->hasOne('Forum_Category as Parent', 'Forum_Category.parent_category_id');
        $this->hasOne('Forum_Category as Rootcategory', 'Forum_Category.root_category_id');
    }
}
class Forum_Board extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string', 5000);
    }
    public function setUp() {
        $this->hasOne('Forum_Category as Category', 'Forum_Board.category_id');
        $this->ownsMany('Forum_Thread as Threads',  'Forum_Thread.board_id');
    } 
}

class Forum_Entry extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('author', 'string', 50); 
        $this->hasColumn('topic', 'string', 100);
        $this->hasColumn('message', 'string', 99999);
        $this->hasColumn('parent_entry_id', 'integer', 10);
        $this->hasColumn('thread_id', 'integer', 10);
        $this->hasColumn('date', 'integer', 10);
    }
    public function setUp() {
        $this->hasOne('Forum_Entry as Parent',  'Forum_Entry.parent_entry_id');
        $this->hasOne('Forum_Thread as Thread', 'Forum_Entry.thread_id');
    }
}

class Forum_Thread extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('board_id', 'integer', 10);
        $this->hasColumn('updated', 'integer', 10);
        $this->hasColumn('closed', 'integer', 1);
    }
    public function setUp() {
        $this->hasOne('Forum_Board as Board', 'Forum_Thread.board_id');
        $this->ownsMany('Forum_Entry as Entries', 'Forum_Entry.thread_id');
    }
}
class App extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 32);
        $this->hasColumn('user_id', 'integer', 11);
        $this->hasColumn('app_category_id', 'integer', 11);
    }
    public function setUp() {
        $this->hasOne('User', 'User.id');
        $this->hasMany('App_Category as Category', 'App_Category.id');
    }        
}

class App_User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('first_name', 'string', 32);
        $this->hasColumn('last_name', 'string', 32);
        $this->hasColumn('email', 'string', 128, 'email');
        $this->hasColumn('username', 'string', 16, 'unique, nospace');
        $this->hasColumn('password', 'string', 128, 'notblank');
        $this->hasColumn('country', 'string', 2, 'country');
        $this->hasColumn('zipcode', 'string', 9, 'nospace');
    }
    public function setUp() {
        $this->hasMany('App', 'App.user_id');
    }    
}
 
class App_Category extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 32);
        $this->hasColumn('parent_id', 'integer');
    }
    public function setUp() {
        $this->hasMany('App', 'App.app_category_id');
        $this->hasMany('App_Category as Parent', 'App_Category.parent_id');
    }
}

class ORM_TestEntry extends Doctrine_Record {
   public function setTableDefinition() {
        $this->setTableName('test_entries');
        $this->hasColumn('id', 'integer', 11, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 255); 
        $this->hasColumn('stamp', 'timestamp');
        $this->hasColumn('amount', 'float'); 
        $this->hasColumn('itemID', 'integer'); 
   } 
    
   public function setUp() {  
        $this->hasOne('ORM_TestItem', 'ORM_TestEntry.itemID'); 
   }
}

class ORM_TestItem extends Doctrine_Record {
   public function setTableDefinition() {
        $this->setTableName('test_items');
        $this->hasColumn('id', 'integer', 11, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 255); 
   } 

   public function setUp() {

        $this->hasOne('ORM_TestEntry', 'ORM_TestEntry.itemID'); 
   } 
}
class ORM_AccessControl extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->hasMany('ORM_AccessGroup as accessGroups', 'ORM_AccessControlsGroups.accessGroupID');
    }
}

class ORM_AccessGroup extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->hasMany('ORM_AccessControl as accessControls', 'ORM_AccessControlsGroups.accessControlID');
    }
}

class ORM_AccessControlsGroups extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('accessControlID', 'integer', 11); 
        $this->hasColumn('accessGroupID', 'integer', 11); 
       
        $this->setPrimaryKey(array('accessControlID', 'accessGroupID'));
    }
}
class EnumTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('status', 'enum', 11, array('values' => array('open', 'verified', 'closed')));
    }
}
class FilterTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string',100);
    }
    public function setUp() {
        $this->ownsMany('FilterTest2 as filtered', 'FilterTest2.test1_id');
    }
}
class FilterTest2 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string',100);
        $this->hasColumn('test1_id', 'integer');
    }
}
class CustomPK extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('uid', 'integer',11, 'autoincrement|primary');
        $this->hasColumn('name', 'string',255);
    }
}
class Log_Entry extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('stamp', 'timestamp');
        $this->hasColumn('status_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('Log_Status', 'Log_Entry.status_id');
    }
}
class CPK_Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->hasMany('CPK_Test2 as Test', 'CPK_Association.test2_id');
    }
}
class CPK_Test2 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->hasMany('CPK_Test as Test', 'CPK_Association.test1_id');
    }
}
class CPK_Association extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('test1_id', 'integer', 11, 'primary');
        $this->hasColumn('test2_id', 'integer', 11, 'primary');
    }
}
class Log_Status extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
}
class ValidatorTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('mymixed', 'string', 100);
        $this->hasColumn('mystring', 'string', 100, 'notnull|unique');
        $this->hasColumn('myarray', 'array', 1000);
        $this->hasColumn('myobject', 'object', 1000);
        $this->hasColumn('myinteger', 'integer', 11);
        $this->hasColumn('myrange', 'integer', 11, array('range' => array(4,123)));
        $this->hasColumn('myregexp', 'string', 5, array('regexp' => '/^[0-9]+$/'));

        $this->hasColumn('myemail', 'string', 100, 'email');
        $this->hasColumn('myemail2', 'string', 100, 'email|notblank');
    }
}
class DateTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('date', 'date', 20);                                    	
    }
}
class GzipTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('gzip', 'gzip', 100000);
    }
}

class Tag extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('Photo', 'Phototag.photo_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('tag', 'string', 100);
    }
}
class Photo extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('Tag', 'Phototag.tag_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100);
    }
}

class Phototag extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('photo_id', 'integer');
        $this->hasColumn('tag_id', 'integer');
    }
}

class BooleanTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('is_working', 'boolean');
        $this->hasColumn('is_working_notnull', 'boolean', 1, array('default' => false, 'notnull' => true));
    }
}
class Data_File extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('filename', 'string');
        $this->hasColumn('file_owner_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('File_Owner', 'Data_File.file_owner_id');
    }
}
class NotNullTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100, 'notnull');
        $this->hasColumn('type', 'integer', 11);                                     	
    }
}
class File_Owner extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
	public function setUp() {
        $this->hasOne('Data_File', 'Data_File.file_owner_id');
    }
}
class MyUser extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
    }
    public function setUp() {
		$this->hasMany('MyOneThing', 'MyOneThing.user_id');
		$this->hasMany('MyOtherThing', 'MyOtherThing.user_id');
    }
}
class MyOneThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
        $this->hasColumn('user_id', 'integer');
    }
    public function setUp() {
		$this->hasMany('MyUserOneThing', 'MyUserOneThing.one_thing_id');
    }
}
class MyOtherThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
        $this->hasColumn('user_id', 'integer');
    }
    public function setUp() {
		$this->hasMany('MyUserOtherThing', 'MyUserOtherThing.other_thing_id');
    }
}
class MyUserOneThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('one_thing_id', 'integer');
    }
}
class MyUserOtherThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('other_thing_id', 'integer');
    }
}
class CategoryWithPosition extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('position', 'integer');
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->ownsMany('BoardWithPosition as Boards', 'BoardWithPosition.category_id');   
    }   
}
class BoardWithPosition extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('position', 'integer');
        $this->hasColumn('category_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('CategoryWithPosition as Category', 'BoardWithPosition.category_id');
    }
}
class Package extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('description', 'string', 255);
    }

    public function setUp()
    {
        $this->ownsMany('PackageVersion as Version', 'PackageVersion.package_id');
    }
}
class PackageVersion extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('package_id', 'integer');
        $this->hasColumn('description', 'string', 255);
    }
    public function setUp()
    {
        $this->hasOne('Package', 'PackageVersion.package_id');
        $this->hasMany('PackageVersionNotes as Note', 'PackageVersionNotes.package_version_id');
    }
}
class PackageVersionNotes extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('package_version_id', 'integer');
        $this->hasColumn('description', 'string', 255);
    }
    public function setUp()
    {
        $this->hasOne('PackageVersion', 'PackageVersionNotes.package_version_id');
    }
}
class NestTest extends Doctrine_Record
{
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
    }
    public function setUp()
    {
        $this->hasMany('NestTest as Parents', 'NestReference.parent_id');
        $this->hasMany('NestTest as Children', 'NestReference.child_id');
    }
}
class NestReference extends Doctrine_Record 
{
    public function setTableDefinition() {
        $this->hasColumn('parent_id', 'integer', 4, 'primary');
        $this->hasColumn('child_id', 'integer', 4, 'primary');
    }
}

class ValidatorTest_Person extends Doctrine_Record {
   public function setTableDefinition() {
      $this->hasColumn('name', 'string', 255, array('notblank', 'unique'));
      $this->hasColumn('is_football_player', 'boolean');
   }
   
   public function setUp() {
      $this->ownsOne('ValidatorTest_FootballPlayer', 'ValidatorTest_FootballPlayer.person_id');
   }
}

class ValidatorTest_FootballPlayer extends Doctrine_Record {
   public function setTableDefinition() {
      $this->hasColumn('person_id', 'string', 255, 'primary');     
      $this->hasColumn('team_name', 'string', 255);
      $this->hasColumn('goals_count', 'integer', 4);
   }
} 

?>
