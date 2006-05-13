<?php
class Entity extends Doctrine_Record {
    public function setUp() {
        $this->ownsOne("Email","Entity.email_id");
        $this->ownsMany("Phonenumber","Phonenumber.entity_id");
        $this->ownsOne("Account","Account.entity_id");
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
    public function setUp() {
        $this->hasColumn("genre","string","30");
    }
    public function setTableDefinition() {
        $this->hasColumn("album_id","integer");
        $this->hasColumn("genre","string",20);
        $this->hasColumn("title","string",30);
    }
}

class Task extends Doctrine_Record {
   public function setUp() {
      $this->hasMany("Resource","Assignment.resource_id");
      $this->hasMany("Task as Subtask","Subtask.parent_id");
   } 
   public function setTableDefinition() {
      $this->hasColumn("name","string",100); 
      $this->hasColumn("parent_id","integer"); 
   } 
} 

class Resource extends Doctrine_Record {
   public function setUp() {
      $this->hasMany("Task","Assignment.task_id");
   } 
   public function setTableDefinition() {
      $this->hasColumn("name","string",100); 
   } 
} 

class Assignment extends Doctrine_Record {
    public function setTableDefinition() {
       $this->hasColumn("task_id","integer"); 
       $this->hasColumn("resource_id","integer"); 
    } 
}
?>
