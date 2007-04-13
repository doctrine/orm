Doctrine offers enum data type emulation for all databases. The enum data type of Doctrine maps to
integer on database. Doctrine takes care of converting the enumerated value automatically to its valuelist equivalent when a record is being fetched
and the valuelist value back to its enumerated equivalent when record is being saved.

<code type="php">
class Article extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("title","string", 200);
        
        // maps to TINYINT on mysql
        $this->hasColumn("section", "enum", 2, array('values' => array("PHP","Python","Java","Ruby")));
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
</code>
