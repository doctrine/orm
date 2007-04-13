
<code type="php">
class Email extends Doctrine_Record {
    public function setUp() {
        $this->setAttribute(Doctrine::ATTR_LISTENER,new MyListener());
    }
    public function setTableDefinition() {
        $this->hasColumn("address","string",150,"email|unique");
    }
}
</code>
