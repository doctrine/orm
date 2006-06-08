<?php
class Entity extends Doctrine_Record {
    public function setUp() {
        $this->ownsOne("Email","Entity.email_id");
        $this->ownsMany("Phonenumber","Phonenumber.entity_id");
        $this->ownsOne("Account","Account.entity_id");
        $this->hasMany("Entity","EntityReference.entity1-entity2");
    }
    public function setTableDefinition() {
        $this->hasColumn("id","integer",20,"autoincrement|primary");
        $this->hasColumn("name","string",50);
        $this->hasColumn("loginname","string",20,"unique");
        $this->hasColumn("password","string",16);
        $this->hasColumn("type","integer",1);
        $this->hasColumn("created","integer",11);
        $this->hasColumn("updated","integer",11);
        $this->hasColumn("email_id","integer");
    }
}
class EntityReference extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("entity1","integer");
        $this->hasColumn("entity2","integer");
        $this->setPrimaryKey(array("entity1","entity2"));
    }
}
class Account extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("entity_id","integer");
        $this->hasColumn("amount","integer");
    }
}

class EntityAddress extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("entity_id","integer");
        $this->hasColumn("address_id","integer");
    }
}

class Address extends Doctrine_Record {
    public function setUp() {
        $this->hasMany("User","Entityaddress.entity_id");
    }
    public function setTableDefinition() {
        $this->hasColumn("address","string",200);
    }
}

// grouptable doesn't extend Doctrine_Table -> Doctrine_Session
// won't initialize grouptable when Doctrine_Session->getTable("Group") is called

class GroupTable { }
class Group extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany("User","Groupuser.user_id");
        $this->setInheritanceMap(array("type"=>1));

    }
}
class Error extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany("Description","Description.file_md5","file_md5");
    }
    public function setTableDefinition() {
        $this->hasColumn("message","string",200);
        $this->hasColumn("code","integer",11);
        $this->hasColumn("file_md5","string",32,"primary");
    }
}
class Description extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("description","string",3000);
        $this->hasColumn("file_md5","string",32);
    }
}
class UserTable extends Doctrine_Table { }
class User extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany("Address","Entityaddress.address_id");
        $this->ownsMany("Album","Album.user_id");
        $this->hasMany("Group","Groupuser.group_id");
        $this->setInheritanceMap(array("type"=>0));
    }
}
class Groupuser extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("added","integer");
        $this->hasColumn("group_id","integer");
        $this->hasColumn("user_id","integer");
    }
}
class Phonenumber extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("phonenumber","string",20);
        $this->hasColumn("entity_id","integer");
    }
}

class Element extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 100);
        $this->hasColumn("parent_id", "integer");
    }
    public function setUp() {
        $this->hasMany("Element as Child","Child.parent_id");
        $this->hasOne("Element as Parent","Element.parent_id");
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("address","string",150,"email|unique");
    }
}
class Album extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany("Song","Song.album_id");
    }
    public function setTableDefinition() {
        $this->hasColumn("user_id","integer");
        $this->hasColumn("name","string",20);
    }
}
class Song extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("album_id","integer");
        $this->hasColumn("genre","string",20);
        $this->hasColumn("title","string",30);
    }
}

class Task extends Doctrine_Record {
   public function setUp() {
      $this->hasMany("Resource as ResourceAlias","Assignment.resource_id");
      $this->hasMany("Task as Subtask","Subtask.parent_id");
   } 
   public function setTableDefinition() {
      $this->hasColumn("name","string",100); 
      $this->hasColumn("parent_id","integer"); 
   }
} 

class Resource extends Doctrine_Record {
   public function setUp() {
      $this->hasMany("Task as TaskAlias","Assignment.task_id");
      $this->hasMany("ResourceType as Type", "ResourceReference.type_id");
   }
   public function setTableDefinition() {
      $this->hasColumn("name","string",100);
   }
}
class ResourceReference extends Doctrine_Record {
    public function setTableDefinition() {
       $this->hasColumn("type_id","integer");
       $this->hasColumn("resource_id","integer");
    }
}
class ResourceType extends Doctrine_Record {
    public function setUp() {
        $this->hasMany("Resource as ResourceAlias", "ResourceReference.resource_id");
    }
    public function setTableDefinition() {
        $this->hasColumn("type","string",100);
    }
}
class Assignment extends Doctrine_Record {
    public function setTableDefinition() {
       $this->hasColumn("task_id","integer"); 
       $this->hasColumn("resource_id","integer"); 
    } 
}
class Forum_Category extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("root_category_id", "integer", 10);
        $this->hasColumn("parent_category_id", "integer", 10);
        $this->hasColumn("name", "string", 50);
        $this->hasColumn("description", "string", 99999);
    }
    public function setUp() {
        $this->hasMany("Forum_Category as Subcategory", "Subcategory.parent_category_id");
        $this->hasOne("Forum_Category as Rootcategory", "Forum_Category.root_category_id");
    }
}
class Forum_Board extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("category_id", "integer", 10);
        $this->hasColumn("name", "string", 100);
        $this->hasColumn("description", "string", 5000);
    }
    public function setUp() {
        $this->hasOne("Forum_Category as Category", "Forum_Board.category_id");
        $this->ownsMany("Forum_Thread as Threads",  "Forum_Thread.board_id");
    } 
}

class Forum_Entry extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("author", "string", 50); 
        $this->hasColumn("topic", "string", 100);
        $this->hasColumn("message", "string", 99999);
        $this->hasColumn("parent_entry_id", "integer", 10);
        $this->hasColumn("thread_id", "integer", 10);
        $this->hasColumn("date", "integer", 10);
    }
    public function setUp() {
        $this->hasOne("Forum_Entry as Parent",  "Forum_Entry.parent_entry_id");
        $this->hasOne("Forum_Thread as Thread", "Forum_Entry.thread_id");
    }
}

class Forum_Thread extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("board_id", "integer", 10);
        $this->hasColumn("updated", "integer", 10);
        $this->hasColumn("closed", "integer", 1);
    }
    public function setUp() {
        $this->hasOne("Forum_Board as Board", "Forum_Thread.board_id");
        $this->ownsMany("Forum_Entry as Entries", "Forum_Entry.thread_id");
    }
}
class App extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 32);
        $this->hasColumn("user_id", "integer", 11);
        $this->hasColumn("app_category_id", "integer", 11);
    }
    public function setUp() {
        $this->hasOne("User","User.id");
        $this->hasMany("App_Category as Category","App_Category.id");
    }        
}

class App_User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("first_name", "string", 32);
        $this->hasColumn("last_name", "string", 32);
        $this->hasColumn("email", "string", 128, "email");
        $this->hasColumn("username", "string", 16, "unique, nospace");
        $this->hasColumn("password", "string", 128, "notblank");
        $this->hasColumn("country", "string", 2, "country");
        $this->hasColumn("zipcode", "string", 9, "nospace");
    }
    public function setUp() {
        $this->hasMany("App","App.user_id");
    }    
}
 
class App_Category extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 32);
        $this->hasColumn("parent_id","integer");
    }
    public function setUp() {
        $this->hasMany("App","App.app_category_id");
        $this->hasMany("App_Category as Parent","App_Category.parent_id");
    }
}

class ORM_TestEntry extends Doctrine_Record {
   public function setTableDefinition() {
        $this->setTableName('test_entries');
        $this->hasColumn("id", "integer", 11, "autoincrement|primary");
        $this->hasColumn("name", "string", 255); 
        $this->hasColumn("stamp", "timestamp");
        $this->hasColumn("amount", "float"); 
        $this->hasColumn("itemID", "integer"); 
   } 
    
   public function setUp() {  
        $this->hasOne("ORM_TestItem", "ORM_TestEntry.itemID"); 
   }
}

class ORM_TestItem extends Doctrine_Record {
   public function setTableDefinition() {
        $this->setTableName('test_items');
        $this->hasColumn("id", "integer", 11, "autoincrement|primary");
        $this->hasColumn("name", "string", 255); 
   } 

   public function setUp() {

        $this->hasOne("ORM_TestEntry", "ORM_TestEntry.itemID"); 
   } 
}
class ORM_AccessControl extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 255);
    }
    public function setUp() {
        $this->hasMany("ORM_AccessGroup as accessGroups", "ORM_AccessControlsGroups.accessGroupID");
    }
}

class ORM_AccessGroup extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 255);
    }
    public function setUp() {
        $this->hasMany("ORM_AccessControl as accessControls", "ORM_AccessControlsGroups.accessControlID");
    }
}

class ORM_AccessControlsGroups extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("accessControlID", "integer", 11); 
        $this->hasColumn("accessGroupID", "integer", 11); 
       
        $this->setPrimaryKey(array("accessControlID", "accessGroupID")); 
    }
}
class Log_Entry extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("stamp", "timestamp");
        $this->hasColumn("status_id", "integer");
    }
    public function setUp() {
        $this->hasOne("Log_Status", "Log_Entry.status_id");
    }
}

class Log_Status extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 255);
    }
}
class Validator_Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("mymixed","string", 100);
        $this->hasColumn("mystring","string", 100, "notnull|unique");
        $this->hasColumn("myarray", "array", 1000);
        $this->hasColumn("myobject", "object", 1000);
        $this->hasColumn("myinteger", "integer", 11);
    }
}
?>
