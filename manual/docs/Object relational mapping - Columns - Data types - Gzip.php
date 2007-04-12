Gzip datatype is the same as string except that its automatically compressed when persisted and uncompressed when fetched. This datatype can be useful when storing data with a large compressibility ratio, such as bitmap images.

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('gziptest', 'gzip');
    }
}
</code>
