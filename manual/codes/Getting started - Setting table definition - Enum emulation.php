<?php
class Article extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("title","string", 200);
        
        // maps to TINYINT on mysql
        $this->hasColumn("section", "enum", 2);
        
        $this->setEnumValues("section", array("PHP","Python","Java","Ruby"));
    }
}
$article = new Article;
$article->title   = 'My first php article';
// doctrine auto-converts the section to integer when the 
// record is being saved
$article->section = 'PHP';
$article->save();

// on insert query with values 'My first php article' and 0 
// would be issued
?>
