Blob (Binary Large OBject) data type is meant to store data of undefined length that may be too large to store in text fields, like data that is usually stored in files.
<br \><br \>
Blob fields are usually not meant to be used as parameters of query search clause (WHERE) unless the underlying DBMS supports a feature usually known as "full text search"

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('blobtest', 'blob');
    }
}
</code>
