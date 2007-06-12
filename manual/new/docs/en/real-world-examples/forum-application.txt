
<code type="php">
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
</code>
